🛒 EVLite-PremierShopping
Sistema de compras personalizadas e infinito para servidores PocketMine-MP (API 2.0.0).

Este plugin permite a los administradores crear un catálogo de compras dinámico directamente desde la config.yml. Es ideal para servidores que buscan optimización y personalización total sin necesidad de múltiples plugins de tiendas.

🚀 Características Principales
Categorías Infinitas: Organiza tus ítems por categorías y subcategorías mediante nodos YAML.

Integración con EV-Core: Diseñado para trabajar en conjunto con el ecosistema de UniverseLand.

Mensajes Dinámicos: Soporte para placeholders de jugador y estado del servidor.

Feedback Sonoro: Sonidos configurables para compras exitosas o saldo insuficiente.

🛠️ Ejemplo de Configuración (config.yml)
YAML

# Configuración de ejemplo para PremierShopping
tienda:
  espadas:
    _texto_: "&b--- SECCIÓN DE ESPADAS ---"
    diamante:
      id: 276
      precio: 500
      _mensaje_: "&aHas comprado una Espada de Diamante por &e$500"
      _sonido_: "positivo"
📦 Instalación
Descarga el código o el .phar.

Sube el archivo a tu carpeta /plugins/.

Configura tus ítems en resources/config.yml.

Reinicia el servidor y ¡listo!

👥 Créditos y Colaboradores
Desarrollador Principal: zTheUnxversePM2_.

Agradecimientos: A Reptilcrak por su veteranía y apoyo en el testing, y a Raid por la asistencia técnica en el staff de UniverseLand.
