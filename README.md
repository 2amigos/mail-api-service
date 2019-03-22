# Mail service

![Mail API Service](assets/mail-service@2x.png)

The Mail service is an Email microservice that sends emails using [mustache-based]((https://github.com/bobthecow/mustache.php)) 
templates. It was built to allow our development teams at [2amigos](https://2amigos.us) to avoid having to configure mail over and over on projects 
involving a microservices infrastructure. It's a combination of two separate applications, one being Symfony's CI application and the other being
an API built with Slim3 as it uses a technique called [spooling](https://symfony.com/doc/current/email/spool.html).  

The project uses [Monolog](https://github.com/Seldaek/monolog) for logging, [Fractal](http://fractal.thephpleague.com/) as a 
serializer, [Tactitian](https://tactician.thephpleague.com/) as a command bus, [gettext](https://packagist.org/packages/gettext/gettext) 
for translations, [Basic access authentication](https://en.wikipedia.org/wiki/Basic_access_authentication) and [Json Web Tokens](https://jwt.io/) 
for authentication (this is optional), and [Zend filter](https://docs.zendframework.com/zend-filter/) for data filtering and validation.    

[Docker compose](https://docs.docker.com/compose/overview/) and [Postman collection](https://www.getpostman.com/) 
files are included for easy development, even though `docker` is not strictly necessary for development as you could easily 
use PHP built-in server.

The project tries to follow DDD principles. 

## Install

Install the latest version using [composer](https://getcomposer.org/).

``` bash
$ composer create-project --no-interaction --stability=dev 2amigos/mail-service app
```

If you are using it from a private repository (using a github url here as an example).

``` bash 
$ composer create-project --no-interaction --stability=dev 2amigos/mail-service app --repository-url=https://github.com/2amigos/mail-service
```

## Configuration

The project uses environment files to configure secrets, for that reason, you must create a file named `.env` at the root 
directory of the project. An `.env.example` file has been provided with all required environment values. Modify that file 
and save it as `.env` in the root directory.

By default, the API application is configured to work under basic authentication processes. It uses an array of users for 
that purpose but you could easily change that behavior by configuring the `authenticator` option of the [HttpBasicAuthentication middleware](https://github.com/tuupola/slim-basic-auth/blob/3.x/src/HttpBasicAuthentication.php#L43) 
by creating your own or using one provided by the library. Check the [PdoAuthenticator](https://github.com/tuupola/slim-basic-auth/blob/3.x/src/HttpBasicAuthentication/PdoAuthenticator.php). 

If authentication is successful, the action will return a Json Web Token to be used for subsequent calls. 

Authentication, or the usage of scopes are optional. If you don't wish to work with this kind of setup, simply remove 
the middleware configurations of `HttpBasicAuthentication`, `JwtAuthentication` and `ScopeMiddleware` middlewares.

The most important part of the application is its `views`, which need to be in the `views/txt` and `views/html` subdirectories.
Their names already explain what type of templates should be in each one.

It also has multi-language support by using `gettext`. An example translation file and template have been provided to help 
you understand how it works. We have also added a command to work with the excellent localization management platform 
called [POEditor](https://poeditor.com/). That command will import for you the translations and transform the files to `.php` 
files. To import the required translations to your project from `POEditor` use this command: 

``` bash 
$ ./bin/console import-translations:run --api-token={POEDITOR_TOKEN} --project={PROJECT_ID} --languages=es,de --delay=3
```

Where {POEDITOR_TOKEN} and {PROJECT_ID} are your token and project id respectively.

The translations will be automatically imported into the `./i18n/` folder. 

In order to work with translations, we have also included a helper class to work with `Mustache` that will parse the 
content and attempt to get the translated content of text within `{{#i18n}}{{/i18n}}` tags. See the example view provided on 
this project.  

## Usage

For the sake of the example, go to the `public` folder of the app and start the built-in PHP server like this: 

``` bash
php -S localhost:8080
``` 

Now we can access the api at `http://127.0.0.1:8080`. 

### Get a token 

To get a token, use the following:

``` bash
$ curl "https://127.0.0.1:8080/token" \
    --request POST \
    --include \
    --insecure \
    --header "Content-Type: application/json" \
    --data '["mail.all"]' \
    --user test:test

HTTP/1.1 201 Created
Content-Type: application/json

{
    "data": {
        "token": "XXXXXXXXXX",
        "expires": 1550271641
    }
}
``` 

### Sending an email (to the spool)

Using the `token`, you can now post a request using `application/form-data` to send an email. 

``` bash 

curl -X POST \
  http://127.0.0.1:8080/mail/send \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -H 'Cache-Control: no-cache' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H 'Postman-Token: 22bf2715-35e4-41ee-a04b-fd8beddcdd62' \
  -H 'content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW' \
  -F from=noreply@example.com \
  -F to=user@example.com \
  -F 'subject=Testing micro-services' \
  -F template=hello-world \
  -F 'data[name]=World' \
  -F language=es \
  -F 'attachments[]=@/path/to/image/to/attach/41835188_10217308479844850_6484466208170049536_o.jpg'
  
```

The above command will create an email message on the spool directory, configured by default at the `runtime` folder. 

#### Parameters 

- `from`: Optional. Who the message is from. If not specified, the mail message will be configured with the 
environment variable named `MAIL_NO_REPLY_EMAIL` (see `.env.example` file).
- `to`: Required. To whom the message was addressed. 
- `subject`: Required. This is what the sender set as the topic of the email content. 
- `template`: Required. The name of the template. For example, if we set this parameter with the value `registration`, 
the system will validate whether a mustache template with the name `registration.mustache` can be found in either 
the `views/txt` or `views/html` folders. 
- `language`: Optional. If the name is translated, set this parameter with the language code you wish to have the message
translated to. For example, if you set it to `es`, it will try to load the translations from the `i18n/es/messages.php` file. 
See `src\Infrastructure\Mustache\Helpers\TranslatorHelper` for further information on loading translations. 


### Sending an email (from the spool)

We use [Symfony's SwiftMailer bundle](https://github.com/symfony/swiftmailer-bundle) to ease the task of sending emails 
from the spool as it comes with some handy commands. From the project root type `$ ./bin/console` on the terminal and 
the following commands will be shown: 

``` bash 
Available commands:
  about                      Displays information about the current project
  help                       Displays help for a command
  list                       Lists commands
 assets
  assets:install             Installs bundles web assets under a public directory
 cache
  cache:clear                Clears the cache
  cache:pool:clear           Clears cache pools
  cache:pool:delete          Deletes an item from a cache pool
  cache:pool:prune           Prunes cache pools
  cache:warmup               Warms up an empty cache
 config
  config:dump-reference      Dumps the default configuration for an extension
 debug
  debug:autowiring           Lists classes/interfaces you can use for autowiring
  debug:config               Dumps the current configuration for an extension
  debug:container            Displays current services for an application
  debug:event-dispatcher     Displays configured listeners for an application
  debug:router               Displays current routes for an application
  debug:swiftmailer          Displays current mailers for an application
 enqueue
  enqueue:consume            [enq:c] A client's worker that processes messages. By default it connects to default queue. It select an appropriate message processor based on a message headers
  enqueue:produce            Sends an event to the topic
  enqueue:routes             [debug:enqueue:routes] A command lists all registered routes.
  enqueue:setup-broker       [enq:sb] Setup broker. Configure the broker, creates queues, topics and so on.
  enqueue:transport:consume  A worker that consumes message from a broker. To use this broker you have to explicitly set a queue to consume from and a message processor service
 import-translations
  import-translations:run    Import POEditor translations command
 lint
  lint:yaml                  Lints a file and outputs encountered errors
 router
  router:match               Helps debug routes by simulating a path info match
 swiftmailer
  swiftmailer:email:send     Send simple email message
  swiftmailer:spool:send     Sends emails from the spool
``` 

To send emails from the spool, simply configure a cron job on your server to run the following command at whatever 
time interval you think is best for your application: 

``` bash 
$ ./bin/console swiftmailer:spool:send --message-limit=10
```

Now, we have to say that whilst `spooling` is a great feature from `SwiftMailer`, this technique is not suitable for 
applications that require sending a high volume of emails. In that case, we highly recommend the usage of a good queue 
library such as [php-enqueue](https://enqueue.forma-pro.com/) with the broker that best suits your knowledge and requirements. 
We recommend `RabbitMQ` as it comes with a manager interface where you will be able to see things such as how many emails 
are being sent, how many have failed, and so on, with the ease of adding/removing as many workers as you need under a possible heavy load. 
It is also `open source`. 

We have provided a working example using [php-enqueue/enqueue-bundle](https://github.com/php-enqueue/enqueue-bundle) which 
comes with a set of very handy commands so you don't need to replicate that work, in conjunction with its 
[Filesystem transport](https://github.com/php-enqueue/fs). The following sections explain how to work with that queue 
system provided.  

### Sending an email (to the filesystem queue)

First what we need to do is to modify the `commandBus` locator map and use the `mail.send.queue.handler` instead of 
`mail.send.spool.handler`: 

``` php 
 $map = [
         CreateTokenCommand::class => 'token.create.handler',
         SendMessageCommand::class => 'mail.send.queue.handler', // must be set like this
     ];
 ```

And that's it. Using the same previous call, this time the message will be sent to the configured queue on the runtime 
folder. 

### Sending an email (from the filesystem queue)

As we said previously, the [php-enqueue/enqueue-bundle](https://github.com/php-enqueue/enqueue-bundle) comes with a set 
of pretty handy commands. For the full reference of those commands, please go to [its documentation](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/bundle/cli_commands.md). 

The one to consume all the messages that go to the queue is `enqueue:consume`: 

``` bash 
$ ./bin/console enqueue:consume mail --no-interaction -vvv --receive-timeout=60000 
``` 

### Using the best of both worlds 

The library also comes with a custom spool so you can use [php-enqueue](https://enqueue.forma-pro.com/) with Swiftmailer. 

In order to use it you will have to make some changes on the code: 

#### Modify mail.send.spool.handler

We need to use the already configured `SendMesssageSpoolHandler` to use our custom `EnqueueSpool` component on the 
`dependencies.php` file:

``` php 
$container['mail.send.spool.handler'] = function ($container) {
    return new SendMessageSpoolHandler(
        $container['enqueue.mailer'], // <--- here is the modification
        $container['mustache'],
        $container['mustache.i18n.helper'],
        $container['fs']
    );
};

```

Then on `services.yaml`, we should refactor the file and make it look like this: 

``` yaml 

swiftmailer.mailer.spool_mailer.spool.custom:
        class: App\Infrastructure\SwiftMailer\EnqueueSpool 
        arguments:
           $context: @enqueue.fs.context
           $queue: 'enqueue.app.mail'
           
        # class: App\Infrastructure\SwitftMailer\FileSpool # commented setting! 
        # arguments:
        #   $path: '%kernel.project_dir%/runtime/spool/default'

    enqueue.mail.processor:
        class: App\Application\Console\Processor\SendMailProcessor
        public: true
        arguments:
            $mailer: '@swiftmailer.mailer.enqueue_mailer'
            $mustache: '@mustache.engine.mail'
            $translatorHelper: '@mustache.i18n.helper'
        tags:
            - { name: 'enqueue.processor', command: '__command__', processorName: 'mail' }

    enqueue.fs.context: 
        class: Enqueue\Fs\FsContext
        arguments:
            $storeDir: '%kernel.project_dir%/runtime/queue'
            $preFetchCount: 1
            $chmod: 600
            $pollingInterval: 100


```

That's it, the way to use it simply follow the guidelines of `sending an email from/to the spool` above.

## Contributing 

To contribute, please read our [CONTRIBUTION guidelines](CONTRIBUTING.md).

## Credits

- [Tuupola slim api skeleton](https://github.com/tuupola/slim-api-skeleton) Thanks for the boilerplate inspiration!
- [2amigos](https://2amigos.us)
- [All Contributors](../../contributors)

## License

The BSD License (BSD). Please see [License File](LICENSE.md) for more information.

> [![2amigOS!](https://s.gravatar.com/avatar/55363394d72945ff7ed312556ec041e0?s=80)](http://www.2amigos.us)  
> <i>Beyond Software</i>  
> [www.2amigos.us](http://www.2amigos.us)
