# Documento de Requisitos — EcoShare

## Introducción

EcoShare es una plataforma de red social enfocada en el **ODS 12 (Producción y Consumo Responsable)** de la ONU. Construida sobre la base técnica del proyecto "Entre Sabores" (Laravel + Vue.js/Inertia), la plataforma permite a usuarios compartir publicaciones sobre temáticas ambientales: reciclaje, economía circular, consumo responsable, reducción de residuos, moda sostenible, energía limpia en el hogar, entre otras.

EcoShare reemplaza el enfoque gastronómico original por uno de impacto ambiental y sostenibilidad, manteniendo las funcionalidades sociales (feed, likes, comentarios, seguidores, notificaciones en tiempo real) y añadiendo análisis de impacto ambiental mediante IA. El despliegue migra de Docker/CapRover a **Vercel** (frontend) con backend Laravel en un servicio compatible (Railway, Render o similar).

---

## Glosario

- **EcoShare**: Nombre de la plataforma. Sistema principal referenciado en los requisitos.
- **Plataforma**: El sistema EcoShare en su conjunto (backend Laravel + frontend Vue.js/Inertia).
- **Usuario**: Persona registrada en EcoShare con perfil público.
- **Publicación**: Contenido creado por un Usuario sobre una temática ODS 12 (texto, imagen opcional, etiquetas).
- **EcoAnálisis**: Análisis generado por IA que evalúa el impacto ambiental y la relevancia ODS 12 de una Publicación. Equivalente al "maridaje" del proyecto base.
- **EcoPuntuación**: Valor numérico (1–10) que el EcoAnálisis asigna a una Publicación según su alineación con el ODS 12.
- **Etiqueta**: Categoría temática asociada a una Publicación (ej: reciclaje, economía circular, residuos, consumo responsable).
- **Feed**: Listado paginado de Publicaciones mostrado en el muro principal.
- **Moderador_IA**: Componente de IA que evalúa si el contenido de una Publicación es relevante para el ODS 12 y no viola las políticas de la plataforma.
- **Seguidor**: Usuario que sigue a otro Usuario para ver sus Publicaciones en el feed "Siguiendo".
- **Notificación**: Aviso en tiempo real enviado a un Usuario cuando ocurre un evento relevante (nuevo seguidor, like, comentario).
- **ContentGuard**: Servicio que detecta instrucciones maliciosas o contenido fuera de contexto en las Publicaciones.
- **EcoUsername**: Nombre de usuario generado automáticamente basado en términos de sostenibilidad y procesos ODS.
- **Vercel**: Plataforma de despliegue para el frontend de EcoShare.
- **WallFeedService**: Servicio backend que gestiona la lógica del Feed (ramas: siguiendo, mixto 70/30, exploración global).
- **PostResource**: Formato JSON estable para serializar Publicaciones en respuestas de la API.

---

## Requisitos

---

### Requisito 1: Reestructuración de identidad y marca

**User Story:** Como administrador del proyecto, quiero que la plataforma refleje la identidad de EcoShare y el ODS 12 en todos sus textos, metadatos y configuraciones, para que los usuarios perciban claramente el enfoque ambiental desde el primer contacto.

#### Criterios de Aceptación

1. THE Plataforma SHALL mostrar el nombre "EcoShare" en el título de la aplicación, metaetiquetas HTML y encabezados de página.
2. THE Plataforma SHALL reemplazar todas las referencias textuales a "Entre Sabores", "maridaje" y terminología gastronómica por equivalentes orientados al ODS 12 en la interfaz de usuario.
3. THE Plataforma SHALL mostrar en la página de inicio una descripción del ODS 12 (Producción y Consumo Responsable) y el propósito de la plataforma.
4. WHEN un Usuario accede a cualquier página pública, THE Plataforma SHALL mostrar metaetiquetas Open Graph con el nombre "EcoShare" y una descripción alineada con el ODS 12.

---

### Requisito 2: Registro de usuarios con EcoUsername

**User Story:** Como nuevo usuario, quiero registrarme en EcoShare con un nombre de usuario sugerido basado en términos de sostenibilidad, para que mi identidad en la plataforma refleje el espíritu ambiental de la comunidad.

#### Criterios de Aceptación

1. WHEN un Usuario completa el formulario de registro, THE Plataforma SHALL generar automáticamente un EcoUsername sugerido combinando el primer nombre del usuario con un término de sostenibilidad seleccionado aleatoriamente de un catálogo predefinido y un número de dos dígitos.
2. THE Plataforma SHALL incluir en el catálogo de términos de sostenibilidad al menos los siguientes conceptos: `recicla`, `circular`, `verde`, `eco`, `sostenible`, `residuo`, `compost`, `reutiliza`, `limpio`, `neutro`, `bioma`, `huella`, `renovable`, `consciente`, `impacto`.
3. WHEN un EcoUsername generado ya existe en la base de datos, THE Plataforma SHALL añadir un sufijo numérico incremental hasta encontrar un EcoUsername único.
4. THE Plataforma SHALL permitir al Usuario ingresar su propio nombre de usuario en lugar del sugerido, siempre que cumpla con el formato permitido (letras, números, puntos y guiones bajos, máximo 30 caracteres).
5. IF el campo de nombre de usuario enviado en el registro está vacío o es inválido, THEN THE Plataforma SHALL usar el EcoUsername generado automáticamente como valor por defecto.
6. THE Plataforma SHALL validar que el nombre de usuario final sea único antes de completar el registro.
7. WHEN un Usuario se registra con un handle de Instagram válido, THE Plataforma SHALL usar ese handle como base del nombre de usuario en lugar del EcoUsername generado.

---

### Requisito 3: Publicaciones enfocadas en ODS 12

**User Story:** Como usuario registrado, quiero crear publicaciones sobre temáticas de producción y consumo responsable, para compartir mis prácticas sostenibles y aprender de la comunidad.

#### Criterios de Aceptación

1. WHEN un Usuario autenticado crea una Publicación, THE Plataforma SHALL requerir un título (máximo 150 caracteres), una descripción (máximo 12.000 caracteres) y al menos una Etiqueta de categoría ODS 12.
2. THE Plataforma SHALL ofrecer un catálogo de Etiquetas temáticas ODS 12 que incluya al menos las categorías: `Reciclaje`, `Economía Circular`, `Consumo Responsable`, `Reducción de Residuos`, `Moda Sostenible`, `Energía Limpia`, `Agricultura Sostenible`, `Agua y Recursos`, `Transporte Verde`, `Educación Ambiental`.
3. THE Plataforma SHALL eliminar los campos `food` y `drink` del formulario de creación de Publicaciones, reemplazándolos por un campo opcional `impacto_estimado` (texto libre, máximo 200 caracteres) donde el Usuario puede describir el impacto ambiental de su práctica.
4. WHEN un Usuario sube una imagen con su Publicación, THE Plataforma SHALL aceptar imágenes en formato JPG, PNG o WebP con un tamaño máximo de 5 MB.
5. WHEN una Publicación es creada exitosamente, THE Plataforma SHALL encolar automáticamente un EcoAnálisis de la Publicación.
6. IF el contenido de una Publicación es detectado como irrelevante para el ODS 12 o contiene instrucciones maliciosas por el ContentGuard, THEN THE Plataforma SHALL rechazar la Publicación con un mensaje explicativo y no la almacenará.
7. WHILE una Publicación tiene `status = active`, THE Plataforma SHALL mostrarla en el Feed a todos los usuarios.
8. IF una Publicación es rechazada por el Moderador_IA, THEN THE Plataforma SHALL notificar al Usuario autor con el motivo del rechazo.

---

### Requisito 4: EcoAnálisis con IA

**User Story:** Como usuario, quiero que mis publicaciones reciban un análisis automático de impacto ambiental, para obtener retroalimentación sobre la relevancia y calidad de mi contribución al ODS 12.

#### Criterios de Aceptación

1. WHEN una Publicación es creada o actualizada, THE Plataforma SHALL encolar un job de EcoAnálisis que evalúe la descripción de la Publicación usando un modelo de IA configurado.
2. THE Plataforma SHALL generar un EcoAnálisis con los siguientes campos: `impacto` (descripción del impacto ambiental, máx. 80 palabras), `alineacion_ods` (cómo se alinea con el ODS 12), `recomendacion` (sugerencia para mejorar el impacto), `score` (EcoPuntuación de 1 a 10).
3. WHEN el EcoAnálisis es completado, THE Plataforma SHALL emitir un evento de broadcasting (`post.analysis.generated`) para actualizar la vista de detalle de la Publicación en tiempo real sin recargar la página.
4. THE Plataforma SHALL usar la EcoPuntuación en el algoritmo de ranking del Feed para los modos "Populares" y "Tendencia", ponderando `likes_count * 2 + comments_count * 3 + score * 2`.
5. WHEN el propietario de una Publicación solicita un re-análisis, THE Plataforma SHALL encolar un nuevo EcoAnálisis y responder con confirmación inmediata.
6. IF el servicio de IA no está disponible o la API key no está configurada, THEN THE Plataforma SHALL registrar una advertencia en los logs y continuar sin EcoAnálisis (la Publicación permanece activa con `analysis_status = failed`).
7. THE Plataforma SHALL reemplazar el prompt de "sommelier gastronómico" por un prompt de "experto en sostenibilidad y ODS 12" en todas las llamadas al modelo de IA.

---

### Requisito 5: Feed y descubrimiento de contenido ODS 12

**User Story:** Como usuario, quiero explorar publicaciones de la comunidad sobre sostenibilidad ordenadas por relevancia, para descubrir prácticas y aprender de otros usuarios comprometidos con el ODS 12.

#### Criterios de Aceptación

1. THE Plataforma SHALL ofrecer un Feed con tres modos de ordenación: `Recientes` (por fecha de creación descendente), `Populares` (por engagement + EcoPuntuación) y `Tendencia` (publicaciones de los últimos 30 días ordenadas por engagement + EcoPuntuación).
2. WHEN un Usuario autenticado accede al Feed en modo `Recientes` sin filtro de "Siguiendo", THE Plataforma SHALL mostrar un feed mixto con aproximadamente 70% de publicaciones de usuarios seguidos y 30% de descubrimiento global.
3. WHEN un Usuario activa el filtro "Siguiendo", THE Plataforma SHALL mostrar únicamente Publicaciones de usuarios que el Usuario sigue.
4. THE Plataforma SHALL permitir filtrar el Feed por una o más Etiquetas ODS 12 simultáneamente.
5. THE Plataforma SHALL permitir buscar Publicaciones por texto en título y descripción.
6. WHEN un visitante no autenticado accede al Feed, THE Plataforma SHALL mostrar el Feed de exploración global sin funcionalidades de interacción (likes, comentarios).
7. THE Plataforma SHALL paginar el Feed con un máximo de 15 publicaciones por página por defecto, configurable hasta 30.
8. WHERE la caché de invitados esté habilitada (`WALL_GUEST_FEED_CACHE_TTL > 0`), THE Plataforma SHALL cachear el Feed de exploración global para visitantes no autenticados.

---

### Requisito 6: Interacciones sociales (likes, comentarios, seguidores)

**User Story:** Como usuario, quiero interactuar con las publicaciones de otros usuarios mediante likes y comentarios, y seguir a usuarios cuyo contenido me inspire, para construir una comunidad activa en torno al ODS 12.

#### Criterios de Aceptación

1. WHEN un Usuario autenticado hace clic en "Me gusta" en una Publicación, THE Plataforma SHALL registrar el like, actualizar el contador en tiempo real vía broadcasting y notificar al autor de la Publicación.
2. WHEN un Usuario autenticado publica un comentario en una Publicación, THE Plataforma SHALL almacenar el comentario, actualizar la vista en tiempo real vía broadcasting y notificar al autor de la Publicación.
3. THE Plataforma SHALL soportar comentarios anidados (respuestas a comentarios) dentro del mismo hilo de una Publicación.
4. WHEN un Usuario autenticado sigue a otro Usuario, THE Plataforma SHALL registrar la relación de seguimiento y notificar al Usuario seguido.
5. WHEN un Usuario autenticado deja de seguir a otro Usuario, THE Plataforma SHALL eliminar la relación de seguimiento sin notificación.
6. THE Plataforma SHALL mostrar el conteo de seguidores y seguidos en el perfil público de cada Usuario.
7. IF un Usuario intenta dar like a su propia Publicación, THEN THE Plataforma SHALL rechazar la acción con un mensaje de error apropiado.

---

### Requisito 7: Notificaciones en tiempo real

**User Story:** Como usuario, quiero recibir notificaciones instantáneas cuando alguien interactúa con mi contenido o me sigue, para mantenerme informado de la actividad de mi comunidad sin necesidad de recargar la página.

#### Criterios de Aceptación

1. WHEN un Usuario recibe un nuevo like, comentario o seguidor, THE Plataforma SHALL emitir una notificación en tiempo real al canal privado del Usuario vía broadcasting.
2. THE Plataforma SHALL mostrar un badge con el conteo de notificaciones no leídas en la barra de navegación, actualizado en tiempo real.
3. WHEN un Usuario marca una notificación como leída, THE Plataforma SHALL decrementar el contador de no leídas y persistir el estado en la base de datos.
4. WHEN un Usuario marca todas las notificaciones como leídas, THE Plataforma SHALL resetear el contador a cero.
5. THE Plataforma SHALL conservar las notificaciones leídas durante al menos 30 días antes de eliminarlas automáticamente.
6. IF el servicio de broadcasting no está disponible (`BROADCAST_CONNECTION=null`), THEN THE Plataforma SHALL continuar funcionando con notificaciones persistidas en base de datos, accesibles mediante polling HTTP.

---

### Requisito 8: Perfiles de usuario

**User Story:** Como usuario, quiero tener un perfil público que muestre mis publicaciones y mi compromiso con el ODS 12, para que otros usuarios puedan conocer mi actividad y seguirme.

#### Criterios de Aceptación

1. THE Plataforma SHALL mostrar en el perfil público de cada Usuario: foto de perfil, nombre completo, EcoUsername, descripción, país, número de publicaciones, seguidores y seguidos.
2. THE Plataforma SHALL mostrar en el perfil público las Publicaciones activas del Usuario ordenadas por fecha de creación descendente, paginadas con máximo 15 por página.
3. WHEN un Usuario actualiza su foto de perfil, THE Plataforma SHALL procesar la imagen en formato WebP y generar variantes de tamaño (miniatura y mediana) para optimizar la carga.
4. THE Plataforma SHALL permitir al Usuario editar su nombre, apellido, descripción, país y foto de perfil desde la página de configuración de cuenta.
5. THE Plataforma SHALL eliminar el campo `instagram` como campo de red social principal del perfil, reemplazándolo por un campo opcional `linkedin` para perfiles profesionales de sostenibilidad.
6. THE Plataforma SHALL reemplazar las preferencias gastronómicas (`PREFERENCE_OPTIONS`) por preferencias de sostenibilidad: `Reciclaje activo`, `Consumo consciente`, `Energía renovable`, `Movilidad sostenible`, `Alimentación plant-based`, `Economía circular`, `Activismo ambiental`, `Educación ecológica`.

---

### Requisito 9: Moderación de contenido con IA

**User Story:** Como administrador de la plataforma, quiero que el contenido publicado sea automáticamente moderado para asegurar que sea relevante para el ODS 12 y no contenga contenido inapropiado, para mantener la calidad y el enfoque de la comunidad.

#### Criterios de Aceptación

1. WHEN una Publicación es creada, THE Moderador_IA SHALL evaluar el contenido de forma asíncrona para determinar si es relevante para el ODS 12 y cumple con las políticas de la plataforma.
2. THE Moderador_IA SHALL clasificar el contenido como `active` (aprobado) o `rejected` (rechazado) basándose en criterios de relevancia ODS 12 y políticas de contenido.
3. IF el Moderador_IA clasifica una Publicación como `rejected`, THEN THE Plataforma SHALL aplicar soft delete a la Publicación y notificar al Usuario autor con el motivo del rechazo.
4. THE ContentGuard SHALL detectar y bloquear intentos de inyección de prompts o instrucciones maliciosas en el contenido de las Publicaciones antes de almacenarlas.
5. WHEN el Moderador_IA completa la evaluación de una Publicación, THE Plataforma SHALL emitir un evento de broadcasting (`post.moderation.updated`) para actualizar la UI sin recarga.
6. THE Plataforma SHALL registrar en logs estructurados todos los eventos de moderación con el ID de la Publicación, el resultado y los motivos.

---

### Requisito 10: Despliegue en Vercel

**User Story:** Como equipo de desarrollo, quiero desplegar EcoShare en Vercel para el frontend y un servicio PaaS compatible para el backend, para eliminar la dependencia de Docker/CapRover y simplificar el ciclo de despliegue.

#### Criterios de Aceptación

1. THE Plataforma SHALL eliminar los archivos de configuración específicos de Docker (`Dockerfile`, `docker-compose.yml`, `captain-definition`) del repositorio principal, o moverlos a una rama/directorio separado de referencia.
2. THE Plataforma SHALL incluir un archivo `vercel.json` con la configuración de rutas y variables de entorno necesarias para el despliegue del frontend en Vercel.
3. THE Plataforma SHALL proporcionar un archivo `.env.vercel.example` con todas las variables de entorno requeridas para el despliegue en Vercel, incluyendo conexión a base de datos, broadcasting, IA y almacenamiento.
4. THE Plataforma SHALL ser compatible con una base de datos MySQL o PostgreSQL gestionada externamente (ej: PlanetScale, Supabase, Railway) sin dependencia de contenedores locales.
5. THE Plataforma SHALL configurar el almacenamiento de archivos (imágenes de perfil y publicaciones) en un servicio de almacenamiento en la nube compatible con el driver S3 de Laravel (ej: AWS S3, Cloudflare R2).
6. WHEN el entorno de despliegue es Vercel, THE Plataforma SHALL usar variables de entorno de Vercel para configurar todos los servicios externos (base de datos, broadcasting, IA, almacenamiento).
7. THE Plataforma SHALL mantener compatibilidad con SQLite para el entorno de tests locales, independientemente del motor de base de datos de producción.

---

### Requisito 11: Observabilidad y salud del sistema

**User Story:** Como equipo de operaciones, quiero monitorear el estado y el rendimiento de EcoShare en producción, para detectar y resolver problemas antes de que afecten a los usuarios.

#### Criterios de Aceptación

1. THE Plataforma SHALL exponer un endpoint `GET /health` que verifique la conectividad con la base de datos, la caché y el sistema de colas, retornando un JSON con el estado de cada componente.
2. THE Plataforma SHALL registrar en logs estructurados (JSON) todos los eventos de negocio relevantes: creación de publicaciones, autenticación, moderación y broadcasting.
3. WHEN se produce un error 5xx, THE Plataforma SHALL registrar el error en el canal de logs estructurados con contexto suficiente para diagnóstico.
4. WHERE el almacenamiento de caché sea Redis (`CACHE_STORE=redis`), THE Plataforma SHALL mantener métricas operativas por ventana de minuto accesibles en `GET /internal/metrics`.
5. THE Plataforma SHALL aplicar rate limiting con nombre en todas las rutas críticas: creación de publicaciones (5/min), feed (60/min), likes, comentarios, follows y autenticación.
