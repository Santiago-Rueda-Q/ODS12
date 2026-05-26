# Documento de Diseño Técnico — EcoShare (ODS 12)

## 1. Arquitectura del Sistema
EcoShare es una plataforma basada en **Laravel 11+** y **Vue.js 3 + Inertia.js**. 
El sistema transiciona de un entorno en contenedores locales (Docker/CapRover) a una arquitectura de despliegue gestionado:
- **Frontend (y capa de presentación):** Desplegado en Vercel (utilizando `vercel-php` o actuando como proxy hacia el backend si se separa, aunque la naturaleza de Inertia sugiere que Laravel correrá en Vercel o el servicio PaaS gestionará ambas capas).
- **Backend / API:** Servicio PaaS como Railway o Render.
- **Base de Datos:** MySQL o PostgreSQL externo gestionado.
- **Almacenamiento:** Amazon S3 o Cloudflare R2 para avatares e imágenes de publicaciones.
- **IA / Moderación:** Integración con API de LLM (ej. OpenAI) para el `Moderador_IA` y `EcoAnálisis`.

## 2. Modelos de Datos (Cambios Principales)

### 2.1 Modelo `User`
- **Nuevos Campos:** `eco_username` (string, único), `linkedin` (string, opcional).
- **Campos Eliminados:** `instagram`.
- **Modificación:** El campo de preferencias debe actualizarse para soportar el nuevo catálogo (`Reciclaje activo`, `Consumo consciente`, etc.).

### 2.2 Modelo `Post`
- **Nuevos Campos:** `impacto_estimado` (string, max 200), `eco_score` (integer, 1-10), `analysis_status` (enum: pending, completed, failed), `eco_analysis` (json para guardar impacto, alineacion_ods y recomendacion), `status` (enum: active, rejected).
- **Campos Eliminados:** `food`, `drink`.

### 2.3 Modelo `Tag`
- **Modificación:** Se usarán categorías exclusivas del ODS 12 (`Reciclaje`, `Economía Circular`, etc.).

## 3. Servicios y Lógica de Negocio

### 3.1 `EcoAnalysisService`
Servicio responsable de interactuar con el LLM para evaluar la publicación.
- Input: Título y descripción del Post.
- Output: JSON con `impacto`, `alineacion_ods`, `recomendacion`, `score`.
- Ejecución: Asíncrona (Jobs/Queues). Emite el evento `post.analysis.generated` vía broadcasting (Pusher/Reverb).

### 3.2 `ContentModeratorService` (Moderador_IA & ContentGuard)
- Valida si el contenido cumple con las políticas y se alinea al ODS 12.
- Detecta inyección de prompts o contenido inapropiado antes de persistir o encola un Job inmediato de revisión que puede pasar el estado del Post a `rejected` (soft delete).

### 3.3 `WallFeedService`
Gestiona la lógica del Feed de publicaciones:
- **Recientes:** Orden por `created_at`.
- **Populares / Tendencia:** Ponderación algorítmica: `(likes_count * 2) + (comments_count * 3) + (eco_score * 2)`.
- **Paginación:** 15 items por página.
- **Caché:** Si es invitado, cachear resultados según TTL configurado.

## 4. Despliegue e Infraestructura
- Eliminación de archivos Docker (`Dockerfile`, `docker-compose.yml`, `captain-definition`).
- Creación de `vercel.json` para definir rutas y el runtime de PHP/Node si aplica.
- Configuración de colas (Redis o SQS) para el procesamiento asíncrono en el PaaS.
- Endpoints de salud (`/health` y `/internal/metrics`) para monitoreo.
