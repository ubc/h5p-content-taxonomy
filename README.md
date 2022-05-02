# UBC H5P Addon - Taxonomy

## Description
The plugin attaches custom taxonomies to h5p content and provide below functionalities.
1. On users' profile page, allowed user to selected one or more faculties they belong to.
2. On h5p content creation/edit page, user can attach faculties and disciplines to h5p content.
3. On h5p content listing page. Both editor and author will be able to view their own h5p contents. However, only editor is able to view h5p contents that is not belong to them but associated with their faculties.

## Local Environment
Install node packages
`npm install`

Start building JS and CSS for development
`npm start`

Build JS and CSS for production
`npm build`

Install phpcs with WordPress coding standard
`composer install`

## Change Log

### 1.0.5
- Refactor including add PHP/JS hooks. UX and performance improvements.