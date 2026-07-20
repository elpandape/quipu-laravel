# Contribuir a quipu-laravel

quipu-laravel es la **integración con Laravel** (MIT) del emisor de facturación electrónica de SUNAT
(`quipu-lite`), con auto-detección de la edición comercial `quipu-pro`. Las contribuciones son bienvenidas:
reportes de bugs, rechazos de SUNAT con evidencia, mejoras a los tests y a la documentación.

La guía completa del proyecto vive en el sitio público:

**👉 <https://quipu.elpanda.pe/integraciones/laravel>**

## Reportar un problema

- **Bug o rechazo de SUNAT:** abre un issue con el XML firmado y el CDR **anonimizados** (sin RUC/nombres
  reales ni credenciales), el código de rechazo y los pasos para reproducir.
- **Vulnerabilidad de seguridad:** **no** abras un issue público — escribe a **contacto@elpanda.pe**.

## Lo esencial

- **Entorno en Docker vía `make`** (no hay PHP local; nunca invoques `php`/`composer`/`vendor/bin/*` directo).
  No necesitas el monorepo de quipu: ambas ediciones llegan como **dependencias versionadas**.
  `elpandape/quipu-lite` se instala desde **Packagist**; `elpandape/quipu-pro` (comercial, `require-dev`)
  desde el **registro privado** `https://packages.elpanda.pe`.

  ```bash
  make install
  make review   # php-cs-fixer + rector + phpstan max+strict + Pest 100 % líneas y tipos
  make fix      # rector + php-cs-fixer
  ```

- **Credenciales de la licencia Pro.** `make install` descarga `quipu-pro` del registro privado, así que
  necesita las credenciales de tu licencia (usuario = el correo de la licencia, contraseña = el token).
  Expórtalas en el entorno del host; `compose.yaml` reenvía `COMPOSER_AUTH` al contenedor:

  ```bash
  export COMPOSER_AUTH='{"http-basic":{"packages.elpanda.pe":{"username":"<correo>","password":"<token>"}}}'
  make install
  ```

  En CI se pasa igual, desde los secretos `QUIPU_LICENSE_USER` y `QUIPU_LICENSE_TOKEN`.

- **Sin licencia Pro también se puede contribuir.** Quita el paquete de tu copia local
  (`docker compose run --rm cli composer remove --dev elpandape/quipu-pro`) y trabaja sobre la parte Lite;
  no incluyas ese cambio de `composer.json`/`composer.lock` en el commit. Lo que pierdes es todo lo que
  cubre la integración con Pro: esas pruebas **no se saltan solas** —dan por hecho que Pro está instalado—,
  así que `make review` no quedará verde de extremo a extremo (fallarán los tests y el análisis estático que
  tocan `src/Pro/`, `src/Console/Pro/` y la composición vía `QuipuPro::for(...)`). Ejecuta el resto en local
  y apóyate en el CI del pull request, que sí instala Pro, para validar esa parte.

- **Calidad:** `make review` debe quedar **verde** antes de cada commit. CI corre la matriz PHP **8.4 / 8.5**
  sobre `orchestra/testbench` (con Laravel 13, la versión que fija el `composer.lock`).
- **Convenciones:** PHP 8.4+, `declare(strict_types=1)`, namespace `ElPandaPe\QuipuLaravel\`, identificadores
  en inglés. La integración se apoya en los `Contract\*` públicos de quipu-lite; no reimplementa la emisión.
- **Tests:** un `*Test.php` contiene solo aserciones. Fixtures en `tests/Factory/*Factory.php` e
  infraestructura en `tests/Support/*.php` (clases PSR-4 tipadas); nunca funciones declaradas dentro de un
  archivo de test.
- **Commits:** Conventional Commits **en español**, sin firma de IA (`feat(tenancy): ...`, `fix(jobs): ...`).
  Rama principal `main`; features en `feature/*`.
