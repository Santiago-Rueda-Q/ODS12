# Plan de Tareas — Migración a EcoShare (ODS 12)

## Fase 1: Reestructuración de Identidad y Configuración
- [ ] 1.1. Modificar `config/app.php` y variables de entorno para usar el nombre "EcoShare".
- [ ] 1.2. Actualizar metaetiquetas HTML, títulos y Open Graph en el layout principal de Vue/Inertia (`app.blade.php` o similar).
- [ ] 1.3. Reemplazar terminología sostenible ("EcoShare", "EcoAnálisis", etc.) por terminología ambiental ("EcoShare", "EcoAnálisis", "Sostenibilidad") en todas las vistas, traducciones (`lang/`) y componentes Vue.
- [ ] 1.4. Actualizar la Landing Page / Inicio para mostrar información sobre el ODS 12.

## Fase 2: Base de Datos y Modelos
- [ ] 2.1. Crear migraciones para actualizar la tabla `users` (añadir `eco_username`, `linkedin`, eliminar `instagram`, actualizar opciones de preferencias).
- [ ] 2.2. Crear migraciones para actualizar la tabla `posts` (añadir `impacto_estimado`, `eco_score`, `analysis_status`, `eco_analysis`, `status`, eliminar `food`, `drink`).
- [ ] 2.3. Ejecutar seeders/actualizar base de datos con las nuevas etiquetas ODS 12 para la tabla `tags`.
- [ ] 2.4. Actualizar Modelos y Factories (`User`, `Post`, `Tag`).

## Fase 3: Autenticación y Perfil de Usuario
- [ ] 3.1. Modificar el controlador de registro para generar y validar el `EcoUsername` basado en el catálogo de términos.
- [ ] 3.2. Actualizar el formulario de registro y perfil (Frontend) para incluir los nuevos campos y las preferencias de sostenibilidad.
- [ ] 3.3. Implementar el procesamiento de imágenes de avatar en formato WebP con redimensionamiento.

## Fase 4: Lógica de Publicaciones y Moderación (IA)
- [ ] 4.1. Modificar los controladores y FormRequests de creación de publicaciones para los nuevos campos (ODS 12).
- [ ] 4.2. Implementar `ContentModeratorService` (ContentGuard) para validar el texto antes de guardarlo.
- [ ] 4.3. Implementar `EcoAnalysisService` para conectar con el LLM, evaluar la publicación y guardar los resultados (`eco_score`, etc.).
- [ ] 4.4. Crear y configurar los Jobs (`EcoAnalysisJob`, `ModerationJob`) para ejecución asíncrona.
- [ ] 4.5. Configurar eventos y broadcasting (ej. `post.analysis.generated`, `post.moderation.updated`) hacia el frontend.

## Fase 5: Feed y Funciones Sociales
- [ ] 5.1. Actualizar `WallFeedService` para incluir la lógica de ordenamiento con `eco_score` (algoritmo de engagement).
- [ ] 5.2. Adaptar la interfaz del Feed para soportar filtros por Etiquetas ODS 12.
- [ ] 5.3. Implementar caché para visitantes no autenticados en el Feed.
- [ ] 5.4. Revisar sistema de notificaciones, likes y comentarios (ajustar UI si es necesario).

## Fase 6: Monitoreo y Salud del Sistema
- [ ] 6.1. Crear endpoint `GET /health` (DB, Cache, Queue).
- [ ] 6.2. Crear endpoint `GET /internal/metrics` (Redis metrics).
- [ ] 6.3. Configurar logs estructurados para eventos de negocio y errores 5xx.
- [ ] 6.4. Aplicar rate limiting en `RouteServiceProvider` o middlewares según los requisitos.

## Fase 7: Despliegue e Infraestructura (Vercel / PaaS)
- [ ] 7.1. Eliminar archivos de Docker locales (o moverlos a otro directorio/rama).
- [ ] 7.2. Crear archivo `vercel.json` para el frontend.
- [ ] 7.3. Crear `.env.vercel.example` con las nuevas variables.
- [ ] 7.4. Probar y validar la conexión a la base de datos externa (Railway/Supabase) y almacenamiento en S3.
