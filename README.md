# Sistema de Monitoreo de Bombas de Agua y Consumo

Sistema de monitoreo del estado operativo de bombas de agua y consumo/nivel de agua en centros de salud de Cercado, Cochabamba.

## Stack

Laravel 12 · PHP 8.2 · MySQL 8.0 · Blade + Tailwind CSS

## Instalación

```bash
git clone https://github.com/Martine39816/monitoreo_Bomba.git
cd monitoreo_Bomba
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configura tu base de datos en `.env`:
```env
DB_DATABASE=monitoreo_bomba
DB_USERNAME=root
DB_PASSWORD=tu_password
```

Crea la base de datos vacía en MySQL, luego:

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

## Acceso inicial
