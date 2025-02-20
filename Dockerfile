FROM thecodingmachine/php:8.2-v4-slim-fpm
USER root

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    build-essential \
    php8.2-dev \
    libclang-dev \
    llvm \
    && rm -rf /var/lib/apt/lists/*

RUN curl https://sh.rustup.rs -sSf | sh -s -- -y

ENV PATH="/root/.cargo/bin:${PATH}"

RUN rustup toolchain install nightly

RUN rustup component add rust-src --toolchain nightly

WORKDIR /app

COPY . .

ENV RUSTFLAGS="-Zlocation-detail=none"

RUN bash -c 'cargo +nightly build -Z build-std=std,panic_abort -Z build-std-features=panic_immediate_abort --release'

RUN strip --strip-all target/release/libphp_prustect.so

RUN llvm-objcopy --strip-unneeded target/release/libphp_prustect.so

# docker build -t rust-php-builder .
# docker create --name rust-builder rust-php-builder
# docker cp rust-builder:/app/target/release/libphp_prustect.so ./libphp_prustect.so
# docker rm rust-builder