# Hymns

This repository contains the Symfony backend for the Hymns project. It powers the API and admin interface, while the public site is rendered as a Vue 3 single-page application.

## Features

- Vue 3 single-page application for the public hymn catalog
- REST API for categories, hymn lookup, searches, and verse data
- EasyAdmin-based admin panel for content management
- Doctrine ORM with MySQL storage
- Telegram bot integration and Sentry monitoring

## Tech stack

- PHP 8.3
- Symfony 6.4
- Doctrine ORM / Doctrine Migrations
- MySQL 8
- Nginx + Docker Compose
- PHPUnit for automated tests

## Notes

This project provides the backend API and admin tools. The public user interface is a Vue 3 SPA that consumes the API and renders hymn content on the client side.
