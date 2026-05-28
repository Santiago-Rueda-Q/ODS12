<?php

namespace Database\Seeders;

use App\Models\Tag;

final class Ods12TagsCatalog
{
    /**
     * @return array<string, list<string>>
     */
    public static function byType(): array
    {
        return [
            Tag::TYPE_ODS12 => self::ods12(),
        ];
    }

    /**
     * @return list<string>
     */
    private static function ods12(): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode("\n", <<<'TXT'
ODS 1: Fin de la Pobreza
ODS 2: Hambre Cero
ODS 3: Salud y Bienestar
ODS 4: Educación de Calidad
ODS 5: Igualdad de Género
ODS 6: Agua Limpia y Saneamiento
ODS 7: Energía Asequible y No Contaminante
ODS 8: Trabajo Decente y Crecimiento Económico
ODS 9: Industria, Innovación e Infraestructura
ODS 10: Reducción de las Desigualdades
ODS 11: Ciudades y Comunidades Sostenibles
ODS 12: Producción y Consumo Responsables
ODS 13: Acción por el Clima
ODS 14: Vida Submarina
ODS 15: Vida de Ecosistemas Terrestres
ODS 16: Paz, Justicia e Instituciones Sólidas
ODS 17: Alianzas para lograr los Objetivos
Reciclaje
Economía Circular
Consumo Responsable
Reducción de Residuos
Moda Sostenible
Energía Limpia
Agricultura Sostenible
Agua y Recursos
Transporte Verde
Educación Ambiental
Compostaje
Cero Basura
Energía Solar
Consumo Local
Huella de Carbono
Reciclaje de Papel
Reciclaje de Cartón
Reciclaje de Plástico
Reciclaje de PET
Reciclaje de HDPE
Reciclaje de PVC
Reciclaje de LDPE
Reciclaje de PP
Reciclaje de PS
Reciclaje de Vidrio
Reciclaje de Metales
Reciclaje de Aluminio
Reciclaje de Acero
Reciclaje de Cobre
Reciclaje de Electrónicos
Reciclaje de Pilas
Reciclaje de Baterías
Reciclaje de Neumáticos
Reciclaje Textil
Reciclaje de Ropa
Reciclaje de Aceite
Reciclaje de Madera
Reutilización
Upcycling
Reparación
Restauración
Renovación
Segunda Mano
Mercado de Pulgas
Intercambio
Donación
Reducción de Plásticos
Cero Plástico
Libre de Plástico
Envases Retornables
Bolsas Reutilizables
Botellas Reutilizables
Sorbetes Ecológicos
Eco-friendly
Sostenibilidad Ambiental
Diseño Sostenible
Eco-Diseño
Arquitectura Sostenible
Materiales Reciclados
Materiales Biodegradables
Materiales Compostables
Bambú
Cáñamo
Algodón Orgánico
Consumo Ético
Comercio Justo
Productos Locales
Km Cero
Mercado Campesino
Agricultura Ecológica
Permacultura
Huerto Urbano
Huerto Escolar
Abono Orgánico
Lombricultura
Biogás
Economía Colaborativa
Movilidad Sostenible
Bicicleta
Transporte Público
Vehículos Eléctricos
Carpooling
Energías Renovables
Energía Eólica
Energía Hidroeléctrica
Energía Geotérmica
Biomasa
Eficiencia Energética
Ahorro de Energía
Ahorro de Agua
Cosecha de Lluvia
Tratamiento de Aguas
Conservación de la Naturaleza
Reforestación
Plantar Árboles
Protección de Bosques
Protección de Océanos
Limpieza de Playas
Limpieza de Ríos
Biodiversidad
Protección de Especies
Rescate Animal
Veganismo
Vegetarianismo
Dietas Basadas en Plantas
Lunes Sin Carne
Gestión de Residuos
Separación en la Fuente
Puntos Ecológicos
Puntos Limpios
Recolección Selectiva
Basura Cero
Emisiones Cero
Neutralidad de Carbono
Bonos de Carbono
Sostenibilidad Corporativa
Responsabilidad Social Empresarial
Compras Sostenibles
Certificación Ecológica
Sello Verde
Eco-Etiquetado
ISO 14001
Desarrollo Sostenible
Innovación Verde
Tecnología Limpia
GreenTech
Startups Ecológicas
Emprendimiento Social
Finanzas Sostenibles
Inversión Verde
Derechos Humanos
Equidad Social
Inclusión
Diversidad
Empoderamiento Femenino
Erradicación de la Pobreza
Seguridad Alimentaria
Nutrición
Salud Pública
Bienestar Mental
Educación Inclusiva
Aprendizaje Permanente
Igualdad Salarial
Trabajo Digno
Infraestructura Resiliente
Industrialización Sostenible
Innovación Abierta
Reducción de Brechas
Urbanismo Táctico
Smart Cities
Movilidad Activa
Gestión del Riesgo
Resiliencia Climática
Adaptación al Cambio Climático
Mitigación
Pesca Sostenible
Conservación Marina
Arrecifes de Coral
Ecosistemas Costeros
Desertificación
Degradación de Tierras
Conservación de Suelos
Justicia Climática
Gobernanza Ambiental
Transparencia
Participación Ciudadana
Cooperación Internacional
Voluntariado
Activismo Ambiental
Greta Thunberg
Fridays for Future
Extinction Rebellion
Greenpeace
WWF
Acción Comunitaria
Liderazgo Juvenil
Conciencia Ambiental
Cultura Sostenible
Hábitos Ecológicos
Estilo de Vida Verde
Minimalismo
Slow Fashion
Slow Food
Consumo Consciente
Desperdicio Cero
Cero Residuos
Rechazar
Reducir
Reusar
Reciclar
Reincorporar
Las 5 R
Gestión Ambiental
Evaluación de Impacto Ambiental
Auditoría Ambiental
Plan de Manejo Ambiental
Legislación Ambiental
Políticas Públicas
Acuerdo de París
Agenda 2030
Objetivos de Desarrollo Sostenible
ODS
Naciones Unidas
PNUMA
Sostenibilidad
Ecología
Medio Ambiente
Naturaleza
Planeta Tierra
Madre Tierra
Pachamama
Cambio Climático
Calentamiento Global
Efecto Invernadero
Contaminación
Contaminación del Aire
Contaminación del Agua
Contaminación del Suelo
Microplásticos
Plásticos de un Solo Uso
Deforestación
Pérdida de Biodiversidad
Extinción
Sobrepesca
Consumismo
Obsolescencia Programada
Fast Fashion
Economía Lineal
Residuos Sólidos Urbanos
RSU
Basura Electrónica
RAEE
Residuos Peligrosos
Residuos Hospitalarios
Residuos Industriales
Residuos Radiactivos
Tratamiento de Residuos
Vertedero
Relleno Sanitario
Incineración
Valorización Energética
Valorización de Residuos
Manejo Integral de Residuos
Logística Inversa
Responsabilidad Extendida del Productor
REP
Análisis de Ciclo de Vida
ACV
Ecodiseño
Simbiosis Industrial
Parque Eco-Industrial
Biomímesis
Cuna a la Cuna
Cradle to Cradle
TXT
        )))));
    }
}
