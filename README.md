# eve/dto
[![Main](https://github.com/Eveio/dto/actions/workflows/main.yml/badge.svg)](https://github.com/Eveio/dto/actions/workflows/main.yml)

A simplistic, flexible Data Transfer Object library written in PHP.

## Why?

It's arguably a common practice to pass data as associative arrays between layers. For example, a service method to create a new user may look like this:

```php
// UserService.php
public function createUser(array $attributes): User
{
    return User::create($attributes);
}
```

The method can be called by e.g., a controller this way:

```php
// UserController.php
public function store(CreateUserRequest $request)
{
    $this->userService->create($request->validated());
}
```

This _kind of_ works, but with several drawbacks:

* An associative array is pretty much unstructured—there's virtually no restriction on what can be put in it or what the data type of each element should be. This makes the code hard to reason about (no exact clue what's inside the array) and can lead to serious security issues.
* One would always have to refer to the documentation (if one exists) for the "shape" of the array. This reduces both reusability and productivity. 
* Static code analysis and IDE auto-completion support are greatly hindered. 

Now imagine instead of using an arbitrary array, we use an object with strongly-typed properties:

```php
// UserCreationData.php
class UserCreationData
{
    public string $email;
    public string $password;
    public ?int $age;
}

// UserService.php
public function createUser(UserCreationData $data): User
{
    return User::create($data->toArray());
}

// UserController.php
public function store(CreateUserRequest $request)
{
    $this->userService->create(UserCreationData::fromRequest($request));
}
```

With this approach, we have a clear idea of what fields to expect as user creation data, their types and other restrictions, and we can enjoy all type-hinting, auto-completion, static analysis etc. This is exactly what eve/dto allows you to do.

## Requirements and Installation

This package requires PHP^7.4. You can install it via Composer:

```bash
composer require eve/dto
```

## Usage

### Basic Usage

Following the example above, firstly, we make `UserCreationData` extends `Eve\DTO\DataTransferObject` and define all attributes as public properties:

```php
class UserCreationData extends \Eve\DTO\DataTransferObject
{
    public string $email;
    public string $password;
    public ?int $age;
}
```

To construct a new `UserCreationData` instance, call `make` with an array of parameters:

```php
$data = UserCreationData::make([
   'email' => 'alice@company.tld',
   'password' => 'SoSecureWow',
   'age' => 30,
]);
```

Alternatively, you can explicitly set the attributes. The code above is exactly the same as

```php
$data = UserCreationData::make();
$data->email = 'alice@company.tld';
$data->password = 'SoSecureWow';
$data->age = 30;
```

Or you can use the fluent `set` method, which can take either an associative array or two separated `$name`, `$value` parameters:

```php
$data = UserCreationData::make()
    ->set('email', 'alice@company.tld')
    ->set([
       'password' => 'SoSecureWow',
       'age' => 30,
    ]);
```

If any of the passed properties doesn't exist in the class definition or if the types don't match (notice that a non-type public property e.g., `public $whatever` accepts all types), an exception will be thrown:

```php
UserCreationData::make(['nope' => 'bar']); // throws "Public property $nope does not exist in class UserCreationData"
UserCreationData::make(['email' => new Foo()]); // throws 'UserCreationData::$email must be of type string, received a value of type Foo.',
```

Then we can call the `toArray()` method to transform the object into an associative array:

```php
$arr = $data->toArray(); // ['email' => 'alice@company.tld', 'password' => 'SoSecureWow', 'age' => 30]
```

Note that non-set properties will NOT be returned in the output array:

```php
$data = UserCreationData::make();

// Only setting email now
$data->email = 'alice@company.tld';

$arr = $data->toArray(); // ['email' => 'alice@company.tld']
```

This is especially handy e.g., if you have a method to patch a database record, as it allows the operation to be totally flexible—you can patch all properties or only a subset of them.

### Type Annotations with DocBlock

Instead of declaring your properties with built-in types, you can use type annotations with DockBlock. This is particularly useful if the property accepts multiple types—in fact, it's the only way to declare such in PHP<8. All type restrictions will be respected as normal:

```php
use Carbon\Carbon;

class NewOrderData \Eve\DTO\DataTransferObject 
{
    /**
     * @var string|Carbon
     */
    public $order_date;
}

NewOrderData::make(['order_date' => '2021-01-02 12:34:56']); // works
NewOrderData::make(['order_date' => Carbon::now()]); // works
NewOrderData::make(['order_date' => false]); // throws
```

### Nested DTOs

Nested DTOs will be transformed into their corresponding arrays:

```php
class UserCreationData extends \Eve\DTO\DataTransferObject
{
    public string $email;
    public string $password;
    public UserInformationData $information;
}

class UserInformationData extends \Eve\DTO\DataTransferObject
{
    public int $age;
}

$data = UserCreationData::make([
   'email' => 'alice@company.tld',
   'password' => 'SoSecureWow',
   'information' => UserInformationData::make(['age' => 30]),
]);

$data->toArray(); // ['email' => 'alice@company.tld', 'password' => 'SoSecureWow', ['information' => ['age' => 30]]
```

### Helper Functions

* `DataTransferObject::only(string ...$names): static` returns the object that includes only `$names` in the output array.

  ```php
  $data = UserCreationData::make([
     'email' => 'alice@company.tld',
     'password' => 'SoSecureWow',
     'age' => 30,
  ]);
  
  $data->only('email', 'password')->toArray(); // ['email' => 'alice@company.tld', 'password' => 'SoSecureWow']
  ```
* `DataTransferObject::except(string ...$names): static` returns the object that exludes `$names` from the output array.

  ```php
  $data = UserCreationData::make([
     'email' => 'alice@company.tld',
     'password' => 'SoSecureWow',
     'age' => 30,
  ]);
  
  $data->except('email', 'password')->toArray(); // ['age' => 30]
  ```
* `DataTransferObject::compact(): static` returns the object that includes only properties whose values are not `NULL` in the output array.

  ```php
  $data = UserCreationData::make([
     'email' => 'alice@company.tld',
     'password' => 'SoSecureWow',
     'age' => null,
  ]);
  
  $data->compact()->toArray(); // ['email' => 'alice@company.tld', 'password' => 'SoSecureWow']
  ```

## Differences from spatie/data-transfer-object

eve/dto is inspired by and shares some similarities with [spatie/data-transfer-object](https://github.com/spatie/data-transfer-object) but the two packages have certain differences, the most significant of which are as follows:

* spatie/data-transfer-object requires all not-null properties to be supplied right from instantiation. This behavior is not always feasible or desirable (refer to the data patching example above). eve/dto opts for a much more forgiving approach, which allows a DTO to be created with any subset of properties.
* spatie/data-transfer-object uses a custom RegExp to parse the docblocks. This approach is prone to bugs and has some limitations. For example, the type-hinted class must be an FQCN (Fully Qualified Class Name) i.e. `\App\Models\Author` instead of `Author`. Meanwhile, eve/dto uses the official [ReflectionDocBlock](https://github.com/phpDocumentor/ReflectionDocBlock) and [TypeResolver](https://github.com/phpDocumentor/TypeResolver) packages from phpDocumentor to deal with docblocks and therefore doesn't have these issues.
* spatie/data-transfer-object implements such features as "Data Transfer Object Collection" and "Flexible Data Transfer Objects." To keep things simple and concise, eve/dto doesn't have these implementations.

## License

MIT
