#![cfg_attr(windows, feature(abi_vectorcall))]
use ext_php_rs::prelude::*;
use std::ffi::CString;
use std::fs;
use obfstr::obfstr;
use hex;
use xxhash_rust::xxh3::xxh3_64;

fn generate_fast_hash(input: &str) -> String {
    let hash = xxh3_64(input.as_bytes());
    format!("{:016x}", hash)
}
fn xor_encrypt_decrypt(data: &[u8], password_bytes: &[u8]) -> Vec<u8> {
    let password_length = password_bytes.len();
    let result: Vec<u8> = data
        .iter() //.par_iter()
        .enumerate()
        .map(|(i, &byte)| {
            let key_byte = password_bytes[i % password_length] & 0xFF;
            byte ^ key_byte
        })
        .collect();
    result
}

#[php_function]
fn prustect(php_file_path: String) {
    let file_name = php_file_path.split('/').last().unwrap();

    let salted_pass = xor_encrypt_decrypt(
        obfstr!("SECRET_PASSWORD").to_string().as_bytes(),
        generate_fast_hash(file_name).as_bytes())
        .into_boxed_slice();

    let encrypted_file = fs::read(php_file_path).unwrap();
    let encrypted_file_parts: Vec<&[u8]> = encrypted_file.split(|&c| c == b'\n').collect();
    let full_part = encrypted_file_parts.get(2).unwrap();
    let encrypted_part = match hex::decode(&full_part[1..]) {
        Ok(decoded) => decoded,
        Err(_) => {
            return
        }
    };
    let decrypted_part = xor_encrypt_decrypt(&encrypted_part, &salted_pass);
    let decrypted_data = String::from_utf8_lossy(&decrypted_part);
    let file_content = format!(
        r#"try {{
    eval(<<<'PHP'
{data}
PHP
    );
}} catch (Throwable $e) {{
    die();
}}"#,
        data = decrypted_data
    );
    let decrypted_content = CString::new(file_content).unwrap();
    unsafe {
        ext_php_rs::ffi::zend_eval_string(
            decrypted_content.as_ptr() as *mut i8,
            std::ptr::null_mut(),
            decrypted_content.as_ptr(),
        );
    }
}

#[php_module]
pub fn get_module(module: ModuleBuilder) -> ModuleBuilder {
    module
}
