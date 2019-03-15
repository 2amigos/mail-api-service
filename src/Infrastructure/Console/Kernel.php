<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Infrastructure\Console;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @inheritdoc
     */
    public function getCacheDir()
    {
        return $this->getProjectDir() . '/runtime/cache/' . $this->environment;
    }

    /**
     * @inheritdoc
     */
    public function getLogDir()
    {
        return $this->getProjectDir() . '/runtime';
    }

    /**
     * @inheritdoc
     */
    public function registerBundles()
    {
        $contents = require $this->getProjectDir() . '/config/console/bundles.php';
        foreach ((array)$contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->setParameter('container.autowiring.strict_mode', true);
        $container->setParameter('container.dumper.inline_class_loader', true);

        $confDir = $this->getProjectDir() . '/config/console';
        $loader->load($confDir . '/packages/*' . self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir . '/../env/' . $this->environment . '/packages/')) {
            $loader->load(
                $confDir . '/../env/' . $this->environment . '/packages-console/**/*' . self::CONFIG_EXTS,
                'glob'
            );
        }
        $loader->load($confDir . '/services' . self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir . '/../env/' . $this->environment . '/services-console')) {
            $loader->load(
                $confDir . '/../env/' . $this->environment . '/services-console/*' . self::CONFIG_EXTS,
                'glob'
            );
        }
    }

    /**
     * @param RouteCollectionBuilder $routes
     *
     * @throws \Symfony\Component\Config\Exception\FileLoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir() . '/config/console';
        if (is_dir($confDir . '/routes/')) {
            $routes->import($confDir . '/routes/*' . self::CONFIG_EXTS, '/', 'glob');
        }
        if (is_dir($confDir . '/../env/' . $this->environment . '/routes-console/')) {
            $routes->import(
                $confDir . '/../env/' . $this->environment . '/routes-console/**/*' . self::CONFIG_EXTS,
                '/',
                'glob'
            );
        }
        $routes->import($confDir . '/routes' . self::CONFIG_EXTS, '/', 'glob');
    }
}
