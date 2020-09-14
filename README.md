# Adopticon
Prototype tooling to aid in the identification and resolution of defects in AdoptOpenJDK's build and test jobs.

## Contents
1) About
2) Setup 
3) Features

## 1) About

The point of this repo is to contain a number of internal-only tools to aid in defect identification and resolution.

These tools should be stored in separate folders so they can be moved or modified easily, with the exception of shared
functionality which should be stored in the relevant file in the include directory (TODO latter).

Each tool should be linked in [index.php](./index.php) for easy access.

This repo is currently made available via a webserver [here](http://adopticon1.fyre.ibm.com/index.html).

## 2) Setup

To set this up on a new machine, here are the linux instructions:

- apt-get install apache2
- apt-get install php libapache2-mod-php
- cd /var/www/html
- rm ./*
- git clone <This repo> .

## 3) Features
- [Nightly Failures Summary](./nightly_failures_summary/index.php)
