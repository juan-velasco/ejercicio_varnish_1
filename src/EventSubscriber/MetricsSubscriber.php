<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\CommentCreatedEvent;
use App\Event\UserCreatedEvent;
use Prometheus\CollectorRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Notifies post's author about new comments.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class MetricsSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CommentCreatedEvent::class => 'onCommentCreated',
            UserCreatedEvent::class => 'onUserCreated',
        ];
    }

    public function onCommentCreated(CommentCreatedEvent $event): void
    {
        \Prometheus\Storage\Redis::setDefaultOptions(
            [
                'host' => 'redis',
                'port' => 6379,
                'password' => null,
                'timeout' => 0.1, // in seconds
                'read_timeout' => '10', // in seconds
                'persistent_connections' => false
            ]
        );

        $registry = CollectorRegistry::getDefault();

        $postSlug = $event->getComment()->getPost()->getSlug();
        $author = $event->getComment()->getAuthor()->getUsername();

        $counter = $registry->registerCounter('blog', 'comments_created_total', 'it increases', ['postSlug', 'author']);
        $counter->incBy(1, [$postSlug, $author]);
    }

    public function onUserCreated(UserCreatedEvent $event)
    {
        \Prometheus\Storage\Redis::setDefaultOptions(
            [
                'host' => 'redis',
                'port' => 6379,
                'password' => null,
                'timeout' => 0.1, // in seconds
                'read_timeout' => '10', // in seconds
                'persistent_connections' => false
            ]
        );

        $registry = CollectorRegistry::getDefault();

        $counter = $registry->registerCounter('blog', 'users_created_total', 'users created');
        $counter->inc();

        $pushGateway = new \PrometheusPushGateway\PushGateway('http://pushgateway:9091');
        $pushGateway->push(\Prometheus\CollectorRegistry::getDefault(), 'user_add_command', ['instance' => 'localhost']);
    }
}
