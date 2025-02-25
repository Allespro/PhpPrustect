# PhpPrustect

![](img/logo.jpg)

README [русский](README.ru.md) / [english](README.md)

**PhpPrustect** — это инструмент для шифрования PHP-кода, написанный на Rust. Он позволяет защитить ваш исходный код и распространять его в зашифрованном виде.
Данный проект прдоставляет код для сборки `.so`/`.dylib`/`.dll` расширения для подключения через `php.ini`, и скрипт `project_obfuscate.php` для обфускации всего PHP проекта.

Проект протестирован и совместим с Laravel.

---

## Важные моменты

Возможно шифрование файлов только полностю состоящих из php кода, вставки html или blade шаблоны могут привести к ошибке.

Не поддерживается `include` и `require` внутри зашифрованного кода, так как код исполняется в памяти через `eval`.

Так как при сборке используется `php-config`, то собранная библиотека становится совместима только с текущим окружением,
потому для удобства приложен `Dockerfile` который позволит собрать расширение для окружения вашего контейнера.

---

## Логика шифрования

1. **Генерация хеша файла**:
    - От каждого файла по его названию создается хеш с помощью алгоритма `xxh3`.

2. **Создание "соленого" пароля**:
    - Пароль XOR-ится с хешем файла, чтобы создать уникальный ключ для шифрования.

3. **Шифрование данных**:
    - Исходный php код XOR-ится с "соленым" паролем.

4. **Результат**:
    - Зашифрованные данные преобразуются в шестнадцатеричную строку с помощью `bin2hex`.

`xxh3` является быстрым алгоритмом хеширования,
использования `hex` для хранения кода позволяет быстрее его преобразовать в бинарные данные,
`xor` операции тоже простые, что уменьшает общую нагрузку при расшифровке.

---

## Сборка модуля

1. Скачайте проект с GitHub:
   ```bash
   git clone https://github.com/allespro/PhpPrustect.git
   cd PhpPrustect
   ```
   
2. Установите свой ключ шифрования в `src/lib.rs` за место `SECRET_PASSWORD`

3. Соберите проект с помощью Cargo:
   ```bash
   rustup toolchain install nightly
   ```
   ```bash
   RUSTFLAGS="-Zlocation-detail=none" cargo +nightly build -Z build-std=std,panic_abort -Z build-std-features=panic_immediate_abort --release
   ```

4. Уменьшите размер файла если это необходимо:
   ```bash
   strip --strip-all target/release/libphp_prustect.so
   ```
   ```bash
   llvm-objcopy --strip-unneeded target/release/libphp_prustect.so
   ```

---

## Шифрование PHP проекта

Для шифрования проекта выполните эти пункты с файлом `project_obfuscate.php`:
- задайте свой секретный ключ в `ENC_PASSWD`;
- укажите путь до проекта в `MAIN_FOLDER`;
- укажите путь где будет создан зашифрованный проект в `OBF_FOLDER`;
- внесите в `$FILES_OBF` файлы которые необходимо зашифровать;
- внесите в `$DO_NOT_COPY` которые не нужны в зашифрованном проекте и их стоит пропустить при копировании;

После настройки запустите процесс шифрования:

```bash
php prustect.php
```

Зашифрованный проект будет в `OBF_FOLDER`

Для проверки работы запустите

```bash
php -d "extension=target/release/libphp_prustect.so" demo/protected_website/index.php
```

---

## Настройка `php.ini`

Подключите собранный модуль в `php.ini` файл и вы готовы использовать шифрование!
```ini
extension=libphp_prustect.so
```

## Лицензия

Этот проект распространяется под лицензией MIT [LICENSE](LICENSE).