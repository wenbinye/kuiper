# Kuiper DI

Kuiper DI 是基于 [php-di](http://php-di.org/) 实现。在 php-di 基础上实现类似 springboot
`@ComponentScan` 包扫描及条件注解特性。

## ContainerBuilder 

DI 容器必须使用 `\kuiper\di\ContainerBuilder` 创建，例如：

```php
<?php
use kuiper\di\ContainerBuilder;

$builder = new ContainerBuilder(); 
```

`ContainerBuilder` 的使用方法和 [php-di](http://php-di.org/doc/container-configuration.html) 介绍的完全一致。 

## Configuration 

DI 容器支持使用 `Configuration` 类配置注入对象。`Configuration` 类就是一般的 PHP 类，只是所有的添加 `@\kuiper\di\annotation\Bean` 
注解的方法将注册到容器中。例如：

```php
<?php
use kuiper\di\annotation\Bean;

class Configuration
{
    /**
     * @Bean()
     */
    public function userRegistrationService(UserRepository $userRepository): UserRegistrationService
    {
        return new UserRegistrationService($userRepository);
    }
}

$builer->addConfiguration(new Configuration());
```

需要注意的是容器中对象定义名字默认使用函数的返回类型。如果函数无返回类型或需要指定定义名字，需要使用 `@Bean` 注解的 `name` 值，例如：

```php
<?php
use kuiper\di\annotation\Bean;

class Configuration
{
    /**
     * @Bean(name="userRegistrationService")
     */
    public function userRegistrationService(UserRepository $userRepository): UserRegistrationService
    {
        return new UserRegistrationService($userRepository);
    }
}
```

函数参数默认使用函数类型查询容器中的对象，如果需要指定参数，需要使用 `@\DI\Annotation\Inject` 注解。注解的使用方式参考 [php-di 文档](http://php-di.org/doc/annotations.html)。

如果需要创建简单定义，或者无法使用方法添加定义，可以通过实现 `\kuiper\di\DefinitionConfiguration` 接口进行定义声明，例如：

```php
<?php

use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

class Configuration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            // place your definitions here
        ];
    }
}
```

`getDefinitions()` 方法中数组定义和 [php-di PHP 定义方式](http://php-di.org/doc/php-definitions.html)相同。

## ComponentScan

DI 容器支持按名字空间扫描名字空间下所有类，识别出所有实现 `\kuiper\di\annotation\ComponentInterface` 接口的注解。
目前支持的注解包括：

- `@\kuiper\di\annotation\Configuration`
- `@\kuiper\di\annotation\Component`
- `@\kuiper\di\annotation\Controller`
- `@\kuiper\di\annotation\Service`
- `@\kuiper\di\annotation\Repository`

`@Configuration` 注解用于标识该类是一个 Configuration 类，将自动添加到容器定义中。

`@Component`, `@Controller`, `@Service`, `@Repository` 四种注解类似 spring 中的注解，可以将当前类添加到容器定义中。
如果注解指定名称，则使用注解中指定的名称作为容器定义名字。否则会将当前类名及当前类实现的所有接口名都作为定义名字注册到容器中。
定义内容为一个 `\DI\Definition\Reference`　类型定义，指向当前类名对应的定义。

扫描过程中如果类使用 `@\kuiper\di\annotation\ComponentScan` 注解，可用于新增新的扫描名字空间。

名字空间扫描是基于 composer PSR-4 规则，使用时必须先注册 Composer Class Loader:

```php
<?php
$loader = require __DIR__ . '/vendor/autoload.php';

$builder->setClassLoader($loader);
```

## Conditional 注解

通过 `@Bean`, `@Component` 等注解注册的定义，可以使用条件注解控制定义生效的条件。目前支持的条件注解包括：

- `@\kuiper\di\annotation\ConditionalOnClass`
- `@\kuiper\di\annotation\ConditionalOnMissingClass`
- `@\kuiper\di\annotation\ConditionalOnBean`
- `@\kuiper\di\annotation\ConditionalOnMissingBean`
- `@\kuiper\di\annotation\ConditionalOnProperty`

`@ConditionalOnClass` 当指定的类存在时生效，而 `@ConditionalOnMissingClass` 则相反，当指定类不存在才生效。

`@ConditionalOnBean` 当容器中指定的名字的定义存在时生效，而 `@ConditionalOnMissingBean` 则相反，当容器中指定名字的定义不存在才生效。

使用 `@ConditionalOnProperty` 注解，需要先在容器中添加一个 `\kuiper\helper\PropertyResolverInterface` 定义。
通过获取对应配置项值判断定义是否生效。

## Aware 类型接口

有时候我们需要像对象中注册一些通用组件，例如 logger。我们可以通过实现 `LoggerAwareInterface` 配合
`LoggerAwareTrait` 让容器自动注入 logger 对象。例如：

```php
<?php

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class FooService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function foo()
    {
        // logger 属性将自动由容器注入
        $this->logger->info("write info log");
    }
}
```

为了实现这个目的，我们需要调用 `ContainerBuilder::addAwareInjection` 方法声明需要统一注册 logger 对象：

```php
<?php
use kuiper\di\AwareInjection;
use Psr\Log\LoggerAwareInterface;

$builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
```

添加这个声明后，所有实现 `LoggerAwareInterface` 的对象都将由容器统一调用 `setLogger` 方法设置
容器中 `Psr\Logger\LoggerInterface` 定义的对象。

## 配置项

通过添加 `PropertiesDefinitionSource` 定义可以从容器中直接读取配置。
当 `application.` 开头的 id，当配置不存在时会返回 null，而不会抛出 `NotFoundException` 异常。

```php
<?php

use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\Properties;

$properties = Properties::create([
    'redis' => [
        'host' => 'localhost'
    ]
]);
$builder->addDefinitions(new PropertiesDefinitionSource($properties));
$container = $builder->build();
$container->get('redis.host');
```

## 通过 composer.json 声明插件机制

```json
{
  "extra": {
    "kuiper": {
      "component-scan": [],
      "configuration": []
    }
  }
}
```
