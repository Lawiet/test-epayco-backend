# 🚀 Proyecto Test Epayco

Este proyecto es una aplicación con arquitectura de **servidor SOAP** y **cliente REST**, que se puede levantar utilizando **Docker Compose** para un entorno aislado y rápido, o de forma **manual** instalando las dependencias necesarias.

## 🐳 Opción 1: Levantar con Docker Compose (Recomendado)

La forma más rápida y recomendada para iniciar el entorno es usando Docker Compose.

### ⚙️ Requisitos

Asegúrate de tener instalado:

* **Docker**
* **Docker Compose** (generalmente ya incluido con Docker Desktop)

### ▶️ Inicio del Proyecto

1.  **Clonar el repositorio:**
    ```bash
    git clone [URL_DEL_REPOSITORIO]
    cd [NOMBRE_DEL_DIRECTORIO]
    ```

2.  **Iniciar los Contenedores:**
    Ejecuta el siguiente comando. Docker Compose construirá las imágenes, creará los contenedores, redes y volúmenes, y ejecutará los *setups* iniciales para instalar dependencias de Composer/NPM y configurar la base de datos (migraciones y *seeding* para el servidor).
    ```bash
    docker-compose up -d --build
    ```
    * La opción `-d` corre los contenedores en segundo plano.
    * La opción `--build` asegura que se use la última configuración de la imagen.

3.  **Verificar el estado:**
    ```bash
    docker-compose ps
    ```
    Todos los servicios deben estar en estado `Up` y los contenedores `setup-test-epayco-server` y `setup-test-epayco-client` deberían haber finalizado su trabajo.

4.  **Acceder a las aplicaciones:**
    Una vez iniciado, podrás acceder a los servicios a través de los siguientes puertos (los puertos por defecto están definidos en el archivo `docker-compose.yml`):

    | Servicio | URL Local (Por defecto) | Puerto (Por defecto) |
        | :--- | :--- | :--- |
    | **Servidor REST/Cliente** | `http://localhost:8000` | `${DOCKER_NGINX_PORT:-8000}` |
    | **Servidor SOAP** | `http://localhost:8001` | `${DOCKER_NGINX_PORT:-8001}` |
    | **phpMyAdmin** | `http://localhost:8090` | `${DOCKER_PMA_PORT:-8090}` |

### 🛑 Detener y Eliminar

* **Detener los contenedores:**
    ```bash
    docker-compose stop
    ```
* **Detener y eliminar contenedores, redes y volúmenes (Limpieza completa):**
    ```bash
    docker-compose down -v
    ```
  ⚠️ **Advertencia:** `-v` elimina los volúmenes, lo que borrará los datos de la base de datos MySQL.

---

## 💻 Opción 2: Instalación Manual

Si prefieres no usar Docker, puedes configurar el proyecto manualmente.

### ⚙️ Requisitos

Necesitarás tener instalados los siguientes programas y dependencias en tu sistema:

* **PHP** (Versión compatible con el Dockerfile)
* **Composer**
* **Node.js y npm**
* **Servidor Web** (Ej. Apache o Nginx)
* **Base de Datos** (Ej. MySQL, compatible con la versión `mysql:9.4`)

### ▶️ Pasos de Instalación

1.  **Clonar el repositorio:**
    ```bash
    git clone [URL_DEL_REPOSITORIO]
    cd [NOMBRE_DEL_DIRECTORIO]
    ```

2.  **Configurar Base de Datos:**
    * Crea una base de datos MySQL (por defecto llamada `app`).
    * Asegúrate de que la configuración de conexión sea accesible.

3.  **Configurar Servidor (ServerSoap):**
    * Navega al directorio del servidor: `cd ServerSoap`
    * Instala dependencias de PHP:
        ```bash
        composer install
        ```
    * Instala dependencias de Node.js:
        ```bash
        npm install
        ```
    * Crea el archivo de entorno (`.env`) si no existe, y genera la clave de la aplicación:
        ```bash
        cp .env.example .env
        php artisan key:generate
        ```
    * Configura las variables de entorno de la base de datos (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, etc.) en el archivo `.env`.
    * Ejecuta las migraciones y *seeds* de la base de datos:
        ```bash
        php artisan migrate --seed
        ```
    * Configura tu servidor web (Nginx/Apache) para que apunte al directorio `ServerSoap/public` y escuche en el puerto `8001`.

4.  **Configurar Cliente (ClientRest):**
    * Navega al directorio del cliente: `cd ../ClientRest`
    * Instala dependencias de PHP:
        ```bash
        composer install
        ```
    * Instala dependencias de Node.js:
        ```bash
        npm install
        ```
    * Crea el archivo de entorno (`.env`) si no existe, y genera la clave de la aplicación:
        ```bash
        cp .env.example .env
        php artisan key:generate
        ```
    * Asegúrate de que la variable `SOAP_WSDL_URL` en el archivo `.env` apunte a la ubicación correcta del WSDL del servidor SOAP (ej. `http://localhost:8001/soap/wallet.wsdl`).
    * Configura tu servidor web (Nginx/Apache) para que apunte al directorio `ClientRest/public` y escuche en el puerto `8000`.