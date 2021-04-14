# eve/dto
[![Main](https://github.com/Eveio/dto/actions/workflows/main.yml/badge.svg)](https://github.com/Eveio/dto/actions/workflows/main.yml)
[![Latest version](https://badgen.net/packagist/v/eve/dto)](https://packagist.org/packages/eve/dto)

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
    $this->userService->create($request->toArray());
}
```

This _kind of_ works, but with several drawbacks:

* An associative array is pretty much unstructured—there's virtually no restriction on what can be put in it or what the data type of each element should be. This makes the code hard to reason about (no exact clue what's inside the array) and can lead to serious security issues.
* One would always have to refer to the documentation (if one exists) for the "shape" of the array. This reduces both reusability and productivity. 
* Static code analysis and IDE auto-completion support are greatly hindered. 

Now imagine instead of using an arbitrary array, we use an object with typed properties:

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

You can install eve/dto via Composer:

```bash
composer require eve/dto
```

This package requires PHP ≥7.4.

### Migrate from v1.x

v1.x versions of this library include a strict type check—for example, assigning a string to a boolean property will throw an error. Though certainly useful, this feature doesn't belong in the scope of a DTO and has been removed from v2. You're encouraged to use a static analysis tool like [PHPStan](https://github.com/phpstan/phpstan) or [Psalm](https://psalm.dev) for the task instead.

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

Alternatively, you can explicitly set the attributes. The code above is essentially the same as:

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

If any of the passed properties doesn't exist in the class definition, an exception will be thrown:

```php
UserCreationData::make(['nope' => 'bar']); // throws "Public property $nope does not exist in class UserCreationData"
```

Then we can call the `toArray()` method to transform the object into an associative array:

```php
$arr = $data->toArray(); // ['email' => 'alice@company.tld', 'password' => 'SoSecureWow', 'age' => 30]
```

Note that non-set properties will NOT be included in the output array:

```php
$data = UserCreationData::make();

// Only setting email now
$data->email = 'alice@company.tld';

$arr = $data->toArray(); // ['email' => 'alice@company.tld']
```

This is especially handy e.g., if you have a method to patch a database record, as it allows the operation to be totally flexible—you can patch all properties or only a subset of them.

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
* `DataTransferObject::get(string $name, $default = null): mixed` returns the value of `$name` property. 
If `$name` doesn't exist in the class definition, an exception will be thrown. If `$name` exists but not initialized, `$default` will be returned.
  > Important: PHP treats non-typed properties e.g., `public $prop` as **initialized with NULL**.
  ```php
  $data = UserCreationData::make([
     'email' => 'alice@company.tld',
     'password' => 'SoSecureWow',
  ]);

  $data->get('email'); // 'alice@company.tld'
  $data->password; // 'SoSecureWow'
  
  $data->age; // throws "UserCreationData::$age must not be accessed before initialization."
  $data->get('age', 30); // 30

  $data->get('nope'); // throws "Public property $nope does not exist in class UserCreationData."
  $data->nope; // throws "Public property $nope does not exist in class UserCreationData."
  ```  

## Differences from spatie/data-transfer-object

Though eve/dto is inspired by and shares some similarities with [spatie/data-transfer-object](https://github.com/spatie/data-transfer-object), the two packages have certain differences, the most significant of which are as follows:

* spatie/data-transfer-object requires all not-null properties to be supplied right from instantiation. This behavior is not always feasible or desirable (refer to the data patching example above). eve/dto opts for a much more forgiving approach, which allows a DTO to be created with any subset of properties. 
* spatie/data-transfer-object can't detect or prevent you from assigning a non-existent property directly (e.g., `$userData->non_existent = 'foo'`), which is something eve/dto does to help ensure your object's integrity.
* spatie/data-transfer-object implements such features as "Data Transfer Object Collection" and "Flexible Data Transfer Objects." To keep things simple and concise, eve/dto doesn't have these implementations.

## License

MIT
