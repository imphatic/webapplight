# webapplight
A Light weight web application wrapper.  Handles fewer of the things you don't need. 

### Author
Garrett R. Davis

### Install
1) Place files inside “www.webapplight.dev” inside root of your project.
2) Add your database credentials. /_app/config/database.php
3) Import install_sql.sql into your database.


### Features
- PSR-0 Autoloader with Aliases. 
- REST capable routing system with apache like defaults if no routes are defined. 
- Environment Manager so you can work local and push at will without having to update settings. 
- Detailed Whoops Error Handling for you, standard, vanilla error message for live production.
- 301 Manager with logs. Managed via the database.
- 404 Logs.
- Advanced Meta data manager via the database, ready for your CMS so your clients can mange it themselves.
- Auto generated sitemaps for search engines. 
- SQL Error logs.
- Configured to force a single request pathway for your content to help improve SEO.
- Minimalistic, abstracted design to keep Web App Light unobtrusive to your applications file structure.  
