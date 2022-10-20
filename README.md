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

### 1.1.6
- Make the embed URL always available even the embed button is disabled by content author. It works the same way as set the 'H5P_EMBED_URL_ALWAYS_AVAILABLE' PHP constant. However, it does allows us to further filter the results.

### 1.1.5
- Bug fix - fixed an issue where no contents will be returned to the list view if 'h5p_add_field_to_query_response' filter returns empty array.

### 1.1.4
- Updated NPM dependency
- Add more JavaScript/PHP filter for content recovery addon

### 1.1.3
- 'Last Updated' timestamp has been switched from UTC time to Local time.

### 1.1.2
- Allow function to be called outside of the app to reset pagination index and fetcing content.

### 1.1.1
- Administrator should see the full faculty list under dropdown without assign to all the faculties under profiles page.
- Administrator should see all the H5P content by default without assign to all the faculties under profiles page.
- Bug fix - Query pagination Index should be reset once any of the filtered changed.

### 1.1.0
- Merged change to master.

### 1.0.9
- Rebuild assets for production.

### 1.0.8
- Updated taxonomy labels.

### 1.0.7
- Enable admin UI for listing view

### 1.0.6
- Hotfix - User login check break H5P embed functionality.

### 1.0.5
- Refactor including add PHP/JS hooks. UX and performance improvements.