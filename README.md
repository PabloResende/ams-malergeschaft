# MVP TailwindCss Project

## Overview

A minimal PHP MVC structure with tailwind, login, registration, and profile dashboard.

## Features

- Secure login and registration using password hashing
- Profile page accessible only after login
- Clean structure with controllers, models, and views
- tailwind UI for styling
- Simple routing using .htaccess
- Diferent languages

## Setup

1. Import `mvp_db.sql` into your MySQL.
2. Set DB credentials in `/config/config.php`.
3. Access `/login` to begin.

## Routes

- `/login` - Login page
- `/register` - Registration form
- `/dashboard` - Dashboard after login
- `/profile` - Profile
- `/logout` - End session
