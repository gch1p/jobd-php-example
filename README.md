# jobd-php-example

This repository contains example of PHP integration with jobd. The code is
mainly an excerpt from a real existent PHP application, with some changes.

To launch this example and see how it works, you will need to set up a jobd-master
instance along with two jobd worker instances.

It was written and tested on my local machine with PHP 7.3, so I'm sharing all
my configs as is. Don't forget to replace values, such as IP addresses,
usernames, passwords and so on, with yours, and generally adjust it to your needs.

## Configuration

### jobd

jobd configs are included in the repo: [`jobd-1.conf`](jobd-1.conf),
[`jobd-2.conf`](jobd-2.conf), [`jobd-master.conf`](jobd-master.conf).

### MySQL

[`schema.sql`](schema.sql) contains schema of MySQL table used in the example.

### Runtime

For the sake of simplicity, runtime configuration (such as MySQL credentials)
is stored in [`init.php`](src/init.php) as global constants. Adjust to your needs.

## Usage

1.  Make sure **MySQL server** is running.

2.  Start **jobd-master** and two **jobd** instances:

    ```
    jobd-master --config jobd-master.conf
    ```
    ```
    jobd --config jobd-1.conf
    ```
    ```
    jobd --config jobd-2.conf
    ```
   
3.  Install dependencies with composer:
    ```
    composer install
    ```
    
4.  Test configuration:
    ```
    php src/main.php test
    ```
    
    This command will test MySQL and jobd connection.

    You can also print the list of workers by executing:
    ```
    jobctl --master list-workers
    ```

5.  Launch test jobs:
    ```
    php src/main.php hello
    ```
    
    This will launch two [`Hello`](src/jobs/Hello.php) jobs, wait for results
    and print them.
    
6.  Launch another test job. [This one](src/jobs/CreateFile.php) will run in
    background. It just creates a file with the name you give it. Not like
    it's anything useful, but it's for the demo.
    ```
    php src/main.php createfile
    ```
    
    Note that if the path your specify is not absolute, it will be relative to 
    the jobd's working directory, specified `launcher.cwd` config option. 
    
    If it fails, just look into the MySQL table, there must be some error.
    

## License

MIT