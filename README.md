# Proyecto Teconoligas Web

Aplicación web PHP para la gestión de productos, inventario, ventas y pedidos.

## Requisitos

- Apache y PHP
- MySQL o MariaDB

## Base de datos

Importa `database/database.sql` en MySQL o MariaDB. El archivo contiene el esquema; se excluyeron los registros locales de usuarios, direcciones, pedidos y ventas.

## Correo SMTP

Para habilitar el envío de correo, configura `SMTP_USERNAME` y `SMTP_PASSWORD` en el entorno disponible para PHP/Apache. No guardes estas credenciales en el repositorio.
