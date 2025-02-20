# PhpPrustect

![](img/logo.jpg)

README [русский](README.ru.md) / [english](README.md)

**PhpPrustect** is a PHP encryption tool written in Rust. It allows you to protect your source code and distribute it encrypted.
This project provides code to build `.so`/`.dylib`/`.dll` extensions to connect via `php.ini`, and a `project_obfuscate.php` script to obfuscate the entire PHP project.

The project is tested and compatible with Laravel controllers, models, etc.

---

## Important points

Only full php files can be encrypted, inserting html or blade templates may cause an error.

It does not support `include` and `require` inside encrypted code, since the code is executed in memory via `eval`.

Since `php-config` is used when building, the built library becomes compatible only with the current environment,
Therefore, for convenience, a `Dockerfile` is attached which will allow you to build an extension for your container environment.

---

## Encryption logic

1. **Generation of file hash**:
    - A hash is generated from each file by its name using the `xxh3` algorithm.

2. **Creation of a “salted” password**:
    - The password is XOR-ed with the file hash to create a unique key for encryption.

3. **Data Encryption**:
    - The source php code is XOR-ed with the “salted” password.

4. **Result**:
    - The encrypted data is converted to a hexadecimal string using `bin2hex`.

`xxh3` is a fast hashing algorithm,
using `hex` to store the code allows it to be converted to binary data faster,
`xor` operations are also simple, reducing the overall decryption burden.

---

## Build module

1. Download the project from GitHub:
   ```bash
   git clone https://github.com/allespro/PhpPrustect.git
   cd PhpPrustect
   ```

2. Set your encryption key in `src/lib.rs` instead of `SECRET_PASSWORD`.

3. Build the project using Cargo:
   ```bash
   rustup toolchain install nightly
   ```
   ```bash
   RUSTFLAGS="-Zlocation-detail=none" cargo +nightly build -Z build-std=std,panic_abort -Z build-std-features=panic_immediate_abort --release
   ```

4. Reduce the file size if necessary:
   ```bash
   strip --strip-all target/release/libphp_prustect.so
   ```
   ```bash
   llvm-objcopy --strip-unneeded target/release/libphp_prustect.so
   ```

---

## Encrypt the PHP project

To encrypt the project, perform these steps with the `project_obfuscate.php` file:
- set your secret key in `ENC_PASSWD`;
- specify the path to the project in `MAIN_FOLDER`;
- specify the path where the encrypted project will be created in `OBF_FOLDER`;
- enter the files to be encrypted into `$FILES_OBF`;
- enter into `$DO_NOT_COPY` the files that are not needed in the encrypted project and should be skipped when copying;

After setting up, start the encryption process:

```bash
php prustect.php
```

The encrypted project will be in `OBF_FOLDER`.

To test it, run

```bash
php -d "extension=target/release/libphp_prustect.so" demo/protected_website/index.php
```

---

## Configure `php.ini`.

Plug the built module into the `php.ini` file and you're ready to use encryption!
```ini
extension=libphp_prustect.so
```

## License

This project is distributed under the MIT [LICENSE](LICENSE).