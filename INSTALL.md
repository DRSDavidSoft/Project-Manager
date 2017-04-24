## INSTALLATION:

0. Before installing, make sure you have MySQL 5.7, PHP 5.6 and above installed. PHP 7.0+ is highly recommended. <br />Your web server should be `.htaccess` compatible (Apache or Litespeed at the moment, I have plans to support IIS and others.
1. Upload the content of `src` to your server, or extract them on your PC.
2. Import the content of `setup.sql` into your database. In later versions, I'll add an automatic SQL importer.
3. In the `index.php` file, edit the 14th and 15th file to reflect your MySQL/MariaDB authentication and table name.
4. Navigate to `http://localhost/project-manager/list` to open the app.

#### After a successful installation, you should have a page similiar to this:
http://refoua.me/project-manager/page/list

At the moment, you can access the `list` and the `edit` page by typing the URLS:
- `http://localhost/project-manager/list`
- `http://localhost/project-manager/edit`

In the future, I'll add a navigation bar.

## NOTES:

1. If you get a 404, make sure your server supports `.htaccess`, your PHP version is compatible, and also make sure that your directory does NOT have any spacs in the name. (`%20`, `+`, ` `)

2. If you get an empty page or a text page, make sure you are either opening `edit` or `list` page. Opening the first page does NOTHING at the moment. (I'll add a redirect later.)

3. If you get `#1064 - You have an error in your SQL syntax;` You have an older MySQL server installed. Please upgrade to `MySQL 5.7`, or contact me if you want to reduce the database compatibility. (A temp. file for 5.6.30 is here: https://ufile.io/z7pip)

Please [create an issue](https://github.com/DRSDavidSoft/Project-Manager/issues) if you have any issues regarding setting up the project.
