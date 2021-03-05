# semver-recommendation
Recommended the semantic version to apply in your master branch using tomzx/php-semver-checker

#Installation
```
composer require --dev jimixjay/semver-recommendation
```

#Usage
Add in your App/Console/Kernel.php the command:

```
protected $commands = [
    ...
    \Jimixjay\SemverRecommendation\SemverRecommendation::class,   
];
```

The current version will compare only the "app" folder of your application. In your console terminal, you can run the command like this.:

```
php artisan semver:recommendation
```

#Recommendations
En esta primera versión es necesario tener la rama remota master actualizada con los cambios que quieras comparar.

Aunque el comando lanza una instrucción git apply sobre tu rama actual al momento de lanzarse para no perder los cambios, recomendamos no tener nada pendiente commit.

In this first version it is necessary to have the remote master branch updated with the changes you want to compare.

Although the command launches a 'git apply' statement on your current branch at launch so as not to lose the changes, we recommend not having anything pending to commit.

#Contribution
Please, note that this command is in alpha version and there will be errors. Contributions are welcome.
