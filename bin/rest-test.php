<?php
use Cilex\Provider\ConfigServiceProvider;
use Orukusaki\MagentoRestTest\AuthoriseCommand;
use Orukusaki\MagentoRestTest\OauthClientProvider;
use Orukusaki\MagentoRestTest\RequestCommand;

require_once __DIR__ . '/../vendor/autoload.php';

define('CONFIG_PATH', 'config.json');

$app = new Cilex\Application('Rest Tester');

if (!file_exists(CONFIG_PATH)) {
    file_put_contents(CONFIG_PATH, '{}');
}

$app->register(new ConfigServiceProvider(), ['config.path' => CONFIG_PATH]);
$app->register(new OauthClientProvider());
$app->command(new AuthoriseCommand());
$app->command(new RequestCommand());

$app->run();
