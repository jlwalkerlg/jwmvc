# MVC PHP framework
by Jordan Walker

## Setup
To get started using the framework, change the following files:

- app/config/config.php: change all constants as appropriate, except for APP_ROOT and PROJECT_ROOT.
- app/core/Database.php: change the DSN if not using the MySQL database driver, otherwise leave this alone.
- public/.htaccess: change the line `RewriteBase /mvc/public` to `RewriteBase /public`.

That's it!

## File Structure
- public/: public assets such as CSS, JavaScript, and images.
- app/cache/: cached HTML files.
- app/config/config.php: configuration settings.
- app/controllers/: custom controllers that should extend the core controller. Can be nested in subdirectories.
- app/core/: JWMVC core classes.
- app/helpers/functions.php: useful helper functions that can be used through the site.
- app/lib/: useful libraries (classes) such as a form validation library.
- app/models/: custom models that should extend the core model.
- app/routes/web.php: where your routes should be registered.
- app/views/: your views.


## Controllers
Custom controllers should extend the core controller:

```php
class Posts extends Controller
{
    ...
}
```

If you wish for any methods with a controller to be restricted to authenticated (logged in) users only, simply define an array of blacklisted methods:
```php
class Posts extends Controller
{
    protected static $auth_blacklist = ['new', 'create', 'edit', 'update', 'delete'];
}
```

To render a particular view, use the `Controller::render()` method:
```php
class Posts extends Controller
{
    public function index()
    {
        $this->render('posts/index');
    }
}
```
This will require the app/views/posts/index.php file and output it as HTML.


## Models
Custom models should extend the core model:
```php
class Post extends Model
{
    ...
}
```

All models should specify which table they represent in the database, as well as a list of fields which can be updated with database queries:
```php
class Post extends Model
{
    protected static $table = 'posts';
    protected static $fillable = ['title', 'body'];
}
```
This is allows JWMVC to exclude inserting particular fields into the database which should be left to upadte automatically, such as an auto-incrementing ID or a timestamp.

JWMVC will assume all records have a primary key named `'id'`. You can override this by specifying a different name:
```php
class Post extends Model
{
    protected $primaryKey = 'postID';
}
```


## Routing
Inspired by Laravel and Node's Express framework, JWMVC uses explicit routing for defining your routes.

The following will direct a GET request to http://example.com/posts to the index method on the Posts controller:
```php
Router::get('/posts', 'Posts@index');
```

The following will direct a GET request to http://example.com/posts/5 to the show method on the Posts controller.
```php
Router::get('/posts/{id}', 'Posts@show');
```

The `id` will be passed to the method as a parameter, allowing it to be captured as follows:
```php
class Posts extends Controller
{
    public function show($id)
    {
        echo $id;
    }
}
```
This method could then choose to use the `$id` to display the corresponding post using the Query Builder.

The following will direct a POST request to http://example.com/posts/5 to the update method on the Posts controller.
```php
Router::post('/posts/{id}', 'Posts@update');
```

The other HTTP verbs are registered ont he router in the same way:
```php
Router::put('/posts/{id}', 'Posts@update');
Router::patch('/posts/{id}', 'Posts@update');
Router::delete('/posts/{id}', 'Posts@update');
```

Note: PUT, PATCH, and DELETE requests must be spoofed, since HTML currently does not support these HTTP verbs. This is done by including a hidden input field in a form with a POST method:
```php
<form action="http://example.com/posts/$id" method="POST">
    <input type="hidden" name="_method" value="PUT">
</form>
```

JWMVC comes with a convenient helper function to make this simple:
```php
<form action="http://example.com/posts/$id" method="POST">
    <?= spoofMethod('PUT') ?>
</form>
```

If you wish to nest your controllers into subdirectories within the app/controllers/ directory, ensure your route reflects this:
```php
Router::get('/posts', 'products\Mens@index');
```

The router also stores the URL in the session for GET requests, allowing controllers to redirect back to the previous page if desired, such as when a form validation fails.

You can also impose regex restrictions on specific parameters in the routes using the Route::where() method:

```php
Router::get('/posts/{id}', 'Posts@show')->where('id', '[0-9]+');
Router::get('/posts/category/{category}/page/{page}', 'Posts@show')->where(['category' => '[A-Za-z]+', 'page' => '[0-9]+']);
```


## Features

### Cache
Caching files is simple, and is inspired by [CodeIgniter's cache usage](https://www.codeigniter.com/userguide3/libraries/caching.html).

To cache the HTML output of any controller method, simply use `$this->cache(n)` anywhere within the method, where `n` is the number of seconds to cache the output for (defaults to 1 day if not provided). This will save a new `.html` file in the app/cache directory, whose name will follow the format `name_spaced_Controller@method.n.html`. Subsequent requests to the same controller method will cause this file to be served directly to the client, without processing the method again. After `n` seconds is up, the next request to the same method will cause the file to be delete from the cache and a then re-written, unless the `$this->cache(n)` line has been removed from the controller method.

```php
class Home extends Controller
{
    public function index()
    {
        echo 'Home page!';

        $this->cache(60*60); // cache the output for 1 hour
    }
}
```

### Query Builder
The query builder is based on [Laravel's query builder](https://laravel.com/docs/5.8/queries), and features much of the most common query methods. Below are some examples.

#### Fetching multiple records with DB::get()
```php
$posts = DB::table('posts')->get(); // fetch all posts
$posts = DB::table('posts')->where('user_id', 10)->get(); // fetch all posts where user_id = 10
$posts = DB::table('posts')->where('user_id', '!=', 10)->get(); // fetch all posts where user_id != 10
$posts = DB::table('posts')->limit(20)->offset(50)->get(); // fetch 20 posts, starting from the 51st
$posts = DB::table('posts')->where('user_id', 10)->limit(10)->offset(10)->get(); // fetch 10 posts where user_id = 10, starting from the 11th
```
All results are returned as an array of object instances of the `stdClass` class.

Note: the order of the query building does not matter, so long as DB::table() is first, and DB::get() is last.

#### Fetching a single record with DB::first()
```php
$posts = DB::table('posts')->first(); // fetch first post
```
This adds a `LIMIT 1` to the query and returns the record directly, rather than an array.

#### Counting records with DB::count()
```php
$posts = DB::table('posts')->count(); // count all posts
```
This returns and integer of the number of records that match the query.

#### Fetching records with DB::join(), DB::leftJoin(), DB::rightJoin(), and DB::select()
```php
$posts = DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->get(); // inner join posts table with users table on posts.user_id = users.id and retrieve all records
$posts = DB::table('posts')->leftJoin('users', 'posts.user_id', '=', 'users.id')->get(); // left join posts table with users table on posts.user_id = users.id and retrieve all records
$posts = DB::table('posts')->rightJoin('users', 'posts.user_id', '=', 'users.id')->get(); // right join posts table with users table on posts.user_id = users.id and retrieve all records
$posts = DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->select('posts.*', 'users.id as userId')->get(); // inner join posts table with users table on posts.user_id = users.id and retrieve all records, returning only the id from the users table
```
Note: SQL indicators such as table and column names can not be bound to prepared statements, so joins and selects are not immune to SQL injection. A simple regex filter is used to deter it, but if dynamic data is used for table or column names it should be manually filtered through an array of whitelisted values first.

#### Inserting records
```php
$key = DB::table('posts')->insert(['title' => 'Post Title', 'body' => 'Here goes the post body...']);
$key = DB::table('posts')->insert([
    ['title' => 'Post Title', 'body' => 'Here goes the post body...'],
    ['title' => 'Post Title #2', 'body' => 'Here goes the second post body...']
]);
```
If at least one record is successfully inserted into the database, DB::insert() returns the primary key of the last inserted record. If no records are inserted, it returns false.

#### Updating records
```php
$result = DB::table('posts')->update(['title' => 'Post Title']); // update all posts
$result = DB::table('posts')->where('id', 10)->update(['title' => 'Post Title']); // update only posts where id = 10
```
Returns the number of records effected by the query.

#### Deleting records
```php
$result = DB::table('posts')->delete(); // delete all posts
$result = DB::table('posts')->where('id', 10)->delete(); // delete posts where id = 10
```
Returns the number of records effected by the query.

#### Direct queries with DB::query()
This method accepts an SQL query string and an optional array of parameters to bind before executing.
```php
$posts = DB::query('SELECT * FROM posts WHERE user_id = ?', [10]);
$posts = DB::query('SELECT * FROM posts WHERE user_id = :user_id', [':user_id' => 10]);
```

### Active Record Pattern
JWMVC implements the active record pattern for easily performing CRUD operations on database records, based on [Laravel's Eloquent ORM](https://laravel.com/docs/5.8/eloquent).

#### Retrieving all records with Model::all()
```php
$post = Post::all();
```

All records are returned as instances of the model class used to retrieve them (the Post model in this example).

#### Retrieving specific records by their primary key with Model::find()
```php
$post = Post::find(10); // find post where primary key = 10
$posts = Post::find([10, 12]); // find posts where primary key = 10 or primary key = 12
```

Note: the primary key is assumed to be named `'id'` by default, but this behaviour can be changed by adding a static property to the class corrseponding a particular model:
```php
class Post extends Model
{
    protected static $primaryKey = 'postId';
}
```

#### Retrieving instantiated records with the query builder
```php
$posts = Post::where('user_id', 10)->limit(10)->offset(50)->get();
$posts = Post::where('user_id', 10)->limit(10)->offset(50)->first();
```

#### Saving a new record
```php
$post = new Post;
$post->title = 'Post Title';
$post->body = 'Here goes the post body...';
$post->save();
```
Returns primary key if record was successfully inserted; false otherwise.

Alternatively:
```php
$post = Post::create(['title' => 'Post Title', 'body' => 'Here goes the post body...']);
```
Returns the inserted record, or false if insert was unsuccessful.

#### Updating an existing record
```php
$post = Post::find(10);
$post->title = 'Post Title (updated)';
$post->save();
```
Returns true if record was successfully updated, or if query had no effect; false otherwise.

#### Deleting a record
```php
$post = Post::find(10);
$post->delete();
```
Returns true if record was successfully deleted; false otherwise.

Alternatively:
```php
Post::destroy(10);
Post::destroy([10, 12]); // delete multiple records based on their primary keys
```
Returns number of records deleted.


### Request
The `Request` class is instantiated upon each request. It stores all user input, instanties all uploaded files, and features built in CSRF protection for POST, PUT, PATCH, and DELETE requests. It can be used to get an element from the user input arrays ($_GET, $_POST, and $_FILES), get an instantiated file, check the request method, store user input for the next request, and access user input from the previous request.

You can use the `request()` helper to grab a reference to the `Request` instance.


### Libraries

#### Form Validation
JWMVC features a form validation library for easily validating form inputs. The core controller features a `Controller::validate()` method which accepts an associative array of validation rules to run against the $_POST and $FILES superglobals.

If validations fail, the user is automatically redirected back to the form and the both the validation error messages and the form values themselves are stored in the session. An array is available from the view under the `$errors` variable, whose keys correspond to the name attributes of the form inputs and whose values are the corrseponding error messages. The submitted values can be captured from the view with the `old()` helper, which accepts string specifying which field to retrieve from the submitted form. If the field does not exist, it will return null.

Usage:

Display the form:
```php
class Posts extends Controller
{
    public function new()
    {
        $this->render('posts/new', $data);
    }
}
```

Create the view for the form, displaying any submitted fields which failed validation:
```php
<form action="<?= url('/posts') ?>" method="POST">
    <?= CSRF::generateInput() ?>

    <input type="text" name="title" value="<?= old('title') ?>">

    ...
</form>
```

Handle the form submission and validate the form fields:
```php
class Posts extends Controller
{
    public function create($id)
    {
        $this->validate([
            'title' => 'required|max:255',
            'body' => 'required|min:10'
        ]);

        // Store new post in database etc...
        $post = new Post;
        $post->assign($_POST);
    }
}
```

Supported validations:
- 'required' -- ensure a field value is set and is not an empty string, or that a file has successfully uploaded to the tmp directory and is not empty
- 'max:n' -- ensure a field value is not larger than n in number, string length, or file size accordingly
- 'min:n' -- ensure a field value is not less than n in number, string length, or file size accordingly
- 'format:email' -- ensure a field value has valid email format
- 'format:date' -- ensure a field value has format xxxx-xx-xx
- 'format:numeric' -- ensure a field value is numeric
- 'format:int' -- ensure a field value is an integer
- 'format:float' -- ensure a field value is a float
- 'format:url' -- ensure a field value has a valid url format
- 'unique:posts' -- ensure a field value is unique among records in posts table
- 'unique:posts,id,2' -- ensure a field value is unique among records in posts table, ignoring the record with a primary key (id) of 2
- 'type:jpg,jpeg' -- ensure an uploaded file matchs the given types


#### File Upload
The file upload library allows you to easily validate uploaded files and store them in a permanent location.

To upload a file, first create a POST form with the `enctype="multipart/form-data"` attribute:

```php
<form action="http://example.com/pic" method="POST" enctype="multipart/form-data">
    <?= CSRF::generateInput() ?>
    <input type="file" name="image" id="image">
    <input type="submit" value="Upload">
</form>
```

Validate the file:
```php
class Users extends Controller
{
    public function uploadProfilePic()
    {
        $this->validate([
            'image' => 'required|max:10240|type:jpg,jpeg'
        ]);

        // File validated; store it in the public/uploads directory.
        $upload = request()->file('image');
        if ($upload->store(PUBLIC_ROOT . '/uploads')) {
            $name = $upload->getName();
            ...
        } else {
            $errors = $upload->getErrors();
        }
    }
}
```

Alternatively:
```php
$upload = new FileUpload($_FILES['image']);
```

By default the uploaded image will keep the same name, unless the file already exists in the destination directory, in which case an integer will be appended to the file name. If you prefer, you can override these and other default behaviours with the `FileUpload::setOptions()` function:
```php
$upload->setOptions([
    'name' => 'profilePic1', // rename the file to profilePic1 (the extension will not change)
    'maxSize' => 1000*1024, // override max size allowed for the file in bytes (default: ini_get('upload_max_filesize'))
    'types' => ['jpg', 'jpeg'], // override permitted file types (default: ['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp'])
    'overwrite' => true, // overwrite any existing file of the same name (default: false).
    'mkdir' => true // create directory if destination does not already exists (default: false)
]);
```

Attempt to store the file, and get any error messages if it fails:
```php
if ($upload->store(PUBLIC_ROOT . '/uploads')) {
    ...
} else {
    $errors = $upload->getErrors();
}
```

The library throws an `Exception` if the destination directory exists but is not a valid, writeable directory; if the destination directory does not exist and fails to be created; and if you attempt to set a max size that exceeds the server limit specified in your php.ini file. These exceptions should be caught, so that the full usage would like something like:

```php
try {
    $upload = new FileUpload($_FILES['image']);
    $upload->setOptions([
        'maxSize' => 1000*1024,
        'name' => 'profilePic1',
        'overwrite' => true,
        'mkdir' => true
    ]);
    if ($upload->store(PUBLIC_ROOT . '/uploads')) {
        $name = $upload->getName();
        ...
    } else {
        $errors = $upload->getErrors();
    }
} catch (Exception $e) {
    $errors = $e->getMessage();
}
```

You may wish to delete a file after uploading it if something goes wrong later in the script. For this, you can use the FileUpload::delete() method:
```php
$upload->delete();
```
This will attempt to delete the file and its parent directory if the directory was created for the file (by setting the mkdir option to true), and returns true if successful, or false otherwise. Note: if the directory fails to delete after the file was deleted, this will return false and the directory will remain.

Currently only a single file may be uploaded per FileUpload instance, and the library only supports jpeg, gif, png, svg, and webp files.


#### CSRF protection
CSRF protection is built in to JWMVC; all incoming POST, PUT, PATCH and DELETE requests are checked for a valid CSRF token. This means that any forms within your application should include a hidden input field containing a CSRF token, which should be generated using the static `CSRF::generateInput()` method:

```php
<form action="http://example.com/posts" method="POST">
    <?= CSRF::generateInput() ?>
    ...
</form>
```


#### Gmail
The Gmail library is build on the [PHPMailer library](https://github.com/PHPMailer/PHPMailer/blob/master/examples/gmail_xoauth.phps). Once you have set your credentials in the app/config/config.php file, you can use the Gmail library to send Gmail as follows:

```php
$to = 'example@hotmail.com';
$subject = 'The subject goes here';
$message = '<p>The main body of the email goes here.</p>';

$gmail = new Gmail;
$gmail->compose($to, $subject, $message);

if ($gmail->send()) {
    ...
} else {
    ...
}
```
Note: the message can include HTML and embedded images in the main body.


#### Paginator
Use the Paginator class to generate a list of pagination links.

Usage:

```php
class Posts extends Controller
{
    public function index($page = 1)
    {
        $page = (int) $page;
        $limit = 5;
        $total = DB::table('posts')->count();

        $paginator = new Paginator('http::example.com/posts/page/__page__', $page, $limit, $total);

        if ($page < 1 || $page > $paginator->getTotalPages()) {
            redirect('/posts');
        }

        $offset = $paginator->getOffset();

        $posts = DB::table('posts')->limit($limit)->offset($offset)->get();

        $data['title'] = 'Posts';
        $data['posts'] = $posts;
        $data['pagination'] = $paginator->shortenedLinks();

        $this->render('posts/index', $data);
    }
}
```

The URL in the Paginator constructor should include a `__page__` substring as in the example above, to be replaced with the page numbers for the links.

Paginator::shortenedLinks() will generate links like `First < N > Last`, where `N` is the current page number.

Paginator::numberedLinks() will generate links like `1 2 3 4 5 ...`.

#### Session
The Session class can be used to get, set and unset session variables easily:

```php
$user_id = Session::get('user_id');
Session::unset('user_id');
Session::set('user_id', 10);
$user_id = Session::getAndUnset('user_id');
```

The Session class also allows you to create flash messages as follows:
```php
Session::flash('success', 'I am the flash message');
```
Then ensure the message is displayed in your view:
```php
<?= Session::flash(); ?>
```
This will return `<p class="flash flash-success">I am the flash message</p>`, then delete the message from the session. If no flash message is set, it will return an empty string.


## Improvements
Improvements planned for future updates to the framework:

- Allow multiple files to be uploaded with one instance of the FileUpload library.
- Database migrations.
