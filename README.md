# aggregate_payment_php
aggregate payment by php



# 项目框架使用 
#### 介绍
在hyperf上封装了ioc/di，对常用的日志、异常处理、请求参数过滤等进行封装，划分数据操作层Dao弱化model耦合性。简单封装websocket接口。

#### 软件架构
MVC（封装IOC/Di）本项目对hyperf进行了进一步延申细化，对Controller、Service、Dao、Model提供便捷的基类，实现长生命周期，协程可用，引入了IOC（控制反转）和DI（依赖注入/容器）的设计思想，业务开发过程中全程无需实例化。

说到实例化的问题，在项目常驻内存，且有高并发场景下，频繁使用new无异于短生命周期，尤其是一个生命周期中重复new了相同的类，特别容易出错。这里最好参考单例模式的思想，并不是说一定要禁止使用实例化，而是避免复用。

hyperf容器内的类都是单例，严禁把单次请求使用的数据储存到类属性中，静态方法也一样。也就是说，在本项目中按照规范书写MVC业务代码，是默认单例的。


#### 安装教程
1. 同hyperf框架，只是简单封装、上层设计模式改变。
2. Php74框架需要的扩展；
3. swoole4.7（4.5存在较多缺陷，建议用新版）；
4. 因为是基于开源框架开发的，上线前一定要封装一些安全策略，生产可用；


#### 目录结构
这里只说明app目录下文件，其他目录结构和原生hyperf框架一样。
    
    app(应用层)
        Aspect（切面）
        Constants（枚举）
        Controller（控制器）
        Exception（抛出类）
        Listener（监听模式）
        Middleware（中间件）
        Model（模型）
        Plugin（插件/组件）
        Process（自定义进程）
        Repositories（仓库）
        Service（服务层）
        Task（任务）
    storage
        view(视图层)
    public(公共资源)



#### 使用说明

1. Controller控制器

AbstractController中注入了容器、请求的接口，供子类使用，BaseController才是本项目的基类。
规范：Controller中自动注入Service（服务）实例，只需要用注解@property注入仓库，即可在子类中直接调用对应模块的仓库。

2. Model模型

继承BaseModel基类，模块划分，尽量不要把业务耦合逻辑写进来，只写复用性强的方法。

3. Plugin(组件|插件)

一些在后续开发中需要用到的组件、客户端，可以放进来，Plugin/Common目录存放比较重要的公用组件，其余组件例如日志类Log单独作为Plugin/Log。

自定义组件内部对代码设计不做什么要求，只要是独立且调用方便就好了，当然建议使用容器调用。

4. Service(服务层)与Dao(数据操作层)

Dao用于控制器和数据操作层之间，主要是封装类似于数据的查询、创建、更新、删除等逻辑，供使用者调用，但在调用过程中不需要考虑更加具体的实现逻辑。
Dao（“数据操作层”）将业务对象交给Service处理，自己处理非业务逻辑。

Service承载着功能性服务或者业务耦合性服务。

使用时，直接通过“隐式注入服务类”自动依赖注入，保证协程的单例模式。