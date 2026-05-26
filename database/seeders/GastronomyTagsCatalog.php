<?php

namespace Database\Seeders;

use App\Models\Tag;

/**
 * Catálogo de nombres de etiquetas (miles de nombres; conteo vía byType()). Slugs en TagSeeder.
 */
final class GastronomyTagsCatalog
{
    /**
     * @return array<string, list<string>>
     */
    public static function byType(): array
    {
        return [
            Tag::TYPE_COUNTRY => self::countries(),
            Tag::TYPE_FOOD_TYPE => self::foods(),
            Tag::TYPE_DRINK => self::drinks(),
            Tag::TYPE_EXPERIENCE => self::experiences(),
        ];
    }

    /**
     * @return list<string>
     */
    private static function countries(): array
    {
        return array_values(array_unique(array_filter(array_map(
            'trim',
            explode("\n", <<<'TXT'
Colombia
México
Argentina
Perú
España
Italia
Japón
Francia
Estados Unidos
Brasil
Chile
Ecuador
Venezuela
Uruguay
Paraguay
Bolivia
Costa Rica
Cuba
Panamá
Puerto Rico
República Dominicana
Guatemala
Honduras
El Salvador
Nicaragua
Canadá
Reino Unido
Alemania
Grecia
Portugal
Suiza
Austria
Bélgica
Países Bajos
Irlanda
Noruega
Suecia
Dinamarca
Finlandia
Polonia
Rusia
Ucrania
Tailandia
Vietnam
China
India
Corea del Sur
Singapur
Australia
Nueva Zelanda
Marruecos
Turquía
Israel
Líbano
Sudáfrica
Egipto
Nigeria
Kenia
Islandia
Chipre
Malta
Eslovenia
Eslovaquia
Chequia
Hungría
Rumanía
Bulgaria
Croacia
Serbia
Caribe colombiano
Pacífico colombiano
Región Andina Colombia
Eje cafetero colombiano
Llanos Orientales Colombia
Amazonía colombiana
Centro de México
Sur de México
Península de Yucatán
Bajío mexicano
Norte de México
Nordeste brasileño
Patagonia argentina
NOA sostenible
Gran Buenos Aires
Costa Caribe suramericana
Mesoamérica culinaria
Cuenca mediterránea europea
Magreb culinario
Levante español
Galicia costera
Asturias culinaria
País Vasco sostenible
Navarra en mesa
La Rioja enología
Cataluña mediterránea
Andalucía tradicional
Extremadura dehesa
Castilla meseta
Provenza culinaria
Normandía láctea
Bretaña atlántica
Alsacia sostenible
Lyon bouchons
París bistró
Lyon sostenible
Algarve portugués
Oporto vitivinícola
Minho verde
Azores insular
Madeira vinícola
Toscana rural
Emilia-Romaña pasta
Lombardía risotti
Piamonte trufas
Sicilia histórica
Puglia horneados
Pizza napolitana origen
Calabria picante
Sicilia dulce
Lazio antipasti
Campania marítima
Liguria pesto
Lago di Como
Alpes italianos
Tirol austriaco
Baviera cervecera
Renania platosa
Sajonia dulce
Praga histórica
Budapest cocido
Transilvania ahumados
Báltico pesquero
Escandinavia nórdica
Fiordos noruegos
Laponia tradicional
Groenlandia insólita
Islandia nórdica
Groenlandia pesca
Shetland marisco
Irlanda pub food
Gales costero
Escocia whisky
Highlands escocesas
Sur de Inglaterra
Cornualles marítima
Lille flamenco
Rennes galettes
Biarritz pincho
San Sebastián pintxo
Bilbao casco
Santiago compostela
Oporto mercado
Cuenca alta Ecuador
Valle de los Chillos
Sierra ecuatoriana
Costa ecuatoriana
Andes peruanos costa
Selva peruana cocina
Arequipa tradicional
Cusco andino
Lima limeña
Costa verde Perú
Altiplano boliviano
Valles bolivianos
Santa Cruz oriental
Chaco paraguayo
Misiones yerba
Corrientes empanada
Mendoza gourmet
Córdoba argentina asado
Rosario parrillero
Bariloche montaña
Neuquén vinos
Río Negro frutos
Tucumán empanada
Mar del Plata marítima
Tijuana frontera
Zona Norte Tijuana
Centro Tijuana
Playas de Tijuana
Ocaña Norte de Santander
Región ocañera
Catatumbo Norte de Santander
TXT
            ),
        ))));
    }

    /**
     * @return list<string>
     */
    private static function foods(): array
    {
        return array_values(array_unique(array_filter(array_map(
            'trim',
            explode("\n", <<<'TXT'
Dulce
Salado
Arepa
Taco
Tacos al pastor
Empanada
Empanadas argentinas
Sushi
Sashimi
Ramen
Pasta carbonara
Lasagna
Risotto
Pizza napolitana
Pizza al taglio
Hamburguesa gourmet
Hot dog callejero
Burrito
Quesadilla
Enchilada
Tamal
Pozole
Mole
Ceviche
Poke bowl
Pad thai
Curry
Butter chicken
Falafel
Shawarma
Kebab
Paella
Gazpacho
Salmorejo
Tortilla española
Churro
Buñuelo
Buñuelos de bacalao
Buñuelos colombianos
Pan de yuca
Almojábana
Pandebono
Bandeja paisa
Ajiaco
Bandeja paisa light
Lechona
Sancocho
Mondongo
Chunchullo
Chicharrón
Carnitas
Cochinita pibil
Barbacoa
Asado argentino
Churrasco
Feijoada
Moqueca
Acarajé
Vatapá
Feijão tropeiro
Brigadeiro
Quindim
Flan
Tarta de limón
Cheesecake
Brownie
Postre tres leches
Alfajor
Oblea
Arepa de choclo
Arepa boyacense
Arepa santandereana
Patacón
Plátano maduro
Yuca frita
Panzerotti
Calzone
Focaccia
Bruschetta
Caprese
Prosciutto
Jamón ibérico
Salami
Chorizo
Morcilla
Morcilla antioqueña
Butifarra
Longaniza
Trucha
Salmón a la plancha
Pescado frito
Filete de res
Costillas BBQ
Pollo a la brasa
Pollo tikka
Kebab de pollo
Albóndigas
Ñoquis
Ravioles
Tortellini
Spaghetti aglio e olio
Pesto genovés
Ñoquis de papa
Yakisoba
Okonomiyaki
Takoyaki
Gyoza
Dim sum
Bao bun
Spring rolls
Rollitos vietnamitas
Pho
Banh mi
Laksa
Tom yum
Satay
Nasi goreng
Rendang
Goulash
Strudel de manzana
Kaiserschmarrn
Waffles
Pancakes
French toast
Croissant
Pain au chocolat
Beignet
Donut artesanal
Bagel
Sandwich cubano
Club sandwich
Alta cocina
Cocina casera
Comida callejera
Fast casual
Cocina tradicional
Cocina contemporánea
Cocina de autor
Cocina fusión
Cocina de mercado
Cocina campesina
Cocina costeña
Cocina andina
Cocina amazónica
Cocina mestiza
Tapeo y raciones
Plato fuerte
Entrada fría
Entrada caliente
Acompañamiento
Botana para compartir
Ácido en boca
Amargo equilibrado
Umami marcado
Ahumado profundo
Especiado aromático
Herbáceo fresco
Notas florales
Crujiente
Cremoso
Jugoso
Meloso
Horneado
Frito
Al vapor
Crudo marinado
Guisado lento
A la parrilla
A la brasa
Ahumado en frío
Fermentado en plato
En escabeche
Confitado
Salteado al wok
Rebozado crocante
Glaseado reducido
Curado en sal
Marinado intenso
Picante moderado
Sin picante
Chilaquiles verdes
Chilaquiles rojos
Entomatadas
Chiles en nogada
Tlayudas
Memelas
Gorditas de maíz
Sopes
Huaraches
Pambazo
Pepian guatemalteco
Kak ik yucateco
Panuchos yucatecos
Salbutes
Relleno negro
Pibipollo
Cochinita horneada
Mixiotes
Discada norteña
Cabrito al pastor
Machaca con huevo
Torta ahogada
Torta de milanesa
Pambazo potosino
Corunda michoacana
Uchepos
Corundas
Itacate
Tlacoyos
Chapulines tostados
Escamoles
Huitlacoche
Tamales oaxaqueños
Tamal colado
Zacahuil
Picadillo a la mexicana
Caldo tlalpeño
Caldo de piedra
Pozole blanco
Pozole verde
Menudo
Pancita
Barbacoa de borrego
Mixiote de pollo
Carne en su jugo
Fríjoles charros
Frijoles puercos
Arroz rojo a la mexicana
Elote asado
Esquites
Gorditas de harina
Semita poblana
Cemitas poblanas
Molotes
Queso fundido con chorizo
Frijoles refritos caseros
Huevos divorciados
Chiles toreados
Nopal asado
Ensalada de nopales
Chile relleno capeado
Chiles capones
Rajas con crema
Tortas de camarón
Pescado zarandeado
Filete de pescado a la veracruzana
Huachinango a la talla
Aguachile rojo
Aguachile verde
Callo de hacha
Mariscos en salsa negra
Arroz con mariscos
Paella valenciana
Paella negra
Paella mixta
Fideuá
Cocido madrileño
Fabada asturiana
Pulpo a la gallega
Pimientos del piquillo
Tortilla de patatas individual
Migas extremeñas
Calçots con romesco
Escalivada catalana
Esqueixada
Suquet de peix
Crema catalana
Mel i mató
Pan con tomate
Xistorra a la sidra
Merluza en salsa verde
Kokotxas de bacalao
Marmitako
Chipirones en su tinta
Arroz caldoso con bogavante
Arroz negro con sepia
Lentejas estofadas
Garbanzos con espinacas
Salmorejo cordobés
Porra antequerana
Gazpacho de cherry
Ajoblanco malagueño
Flamenquín cordobés
Rabo de toro
Carrillada ibérica
Secreto ibérico
Presa ibérica
Lagarto ibérico
Pluma ibérica
Chuletón a la brasa
Chuletón de Ávila
Cordero asado castellano
Cochinillo segoviano
Lechazo riojano
Perdiz escabechada
Conejo al ajillo
Codillo a la bávara
Schnitzel vienés
Käsespätzle
Currywurst
Bratkartoffeln
Labskaus
Gravlax nórdico
Smørrebrød danés
Frikadeller
Æblekage
Köttbullar
Jansson frestelse
Pytt i panna
Lohikeitto
Karjalanpiirakka
Moussaka griega
Pastitsio
Souvlaki
Gyros
Tzatziki casero
Dolmades
Spanakopita
Tiropita
Revani
Baklava artesanal
Künefe
Menemen
Menemen con sucuk
Lahmacun
Pide turca
Adana kebab
İskender kebab
Manti turco
Mercimek çorbası
Köfte casero
Shakshuka
Jachnun
Malabi
Kibbeh frito
Tabule fresco
Fattoush
Manakish
Musakhan
Mansaf jordano
Koshari egipcio
Ful medames
Molokhia
Tagine de cordero
Couscous marroquí
Harira
Pastela moruna
Brik tunecino
Jollof rice
Suya nigeriana
Injera etíope
Doro wat
Kitfo
Bobotie sudafricana
Biltong
Bobotie vegetariana
Poutine clásica
Tourtière québequesa
Bagels salmón
Comida de mar
Mariscos frescos del día
Coctel de camarón tradicional
Coctel de jaiba
Vuelve a la vida mariscos
Aguachile negro
Aguachile de camarón
Tostada de ceviche
Tostada de atún
Tiradito de pescado
Tiradito en crema de ají
Filete de robalo
Filete de sierra
Pargo entero frito
Pargo zarandeado
Pargo a la diabla
Mojarra frita entera
Mojarra al mojo de ajo
Huachinango entero al vapor
Langosta al thermidor
Langosta al mojo de ajo
Camarón a la diabla
Camarón al mojo de ajo
Camarón empanizado
Camarón capeado
Camarón en chipotle
Camarón cucaracha
Camarón gigante a la plancha
Ostiones en su concha
Ostiones gratinados
Callo de hacha natural
Almejas a la parmesana
Mejillones en salsa roja
Mejillones al vapor con vino
Pulpo al carbón
Pulpo en aceite de oliva
Pulpo en escabeche
Calamar a la romana
Calamar relleno de mariscos
Calamar en su tinta
Chipirones rellenos
Arañas fritas
Jaiba entera al mojo
Jaiba en salsa verde
Jaiba reina al vapor
Machaca de jaiba
Surubí al horno
Bagre en salsa
Corvina en salsa veracruzana
Corvina al coco
Dorado a la talla
Dorado a la plancha
Atún sellado tataki
Atún empanizado
Salmón con costra de hierbas
Parihuela peruana
Sudado de pescado norteño
Sudado de mariscos
Chupe de camarones
Chupe de mariscos
Zarandeado de raya
Caldo de camarón con verduras
Sopa de mariscos cremosa
Mariscada en salsa mantequilla
Arroz verde con mariscos
Arroz chaufa de mariscos
Pasta con mariscos
Spaghetti fruti di mare
Linguini con almejas
Risotto de mariscos
Paella de mariscos
Pescado empapelado estilo Sinaloa
Filete empapelado al cilantro
Parihuela mixta grande
Salpicón de jaiba
Ensalada de pulpo mediterránea
Ensalada de atún fresco
Tacos de pescado estilo Ensenada
Tacos de camarón capeado
Burrito de mariscos
Burrito surf and turf
Hot dog de camarón bacon
Mariscos guisados con verduras
Almejas en salsa de vino
Almejas al vapor con limón
Percebes al vapor
Mejillones tigre
Callos de almeja mantequilla
Almejas negras del Pacífico
Torta de harina de trigo Tijuana
Carne asada tijuanense
Tacos de adobada Tijuana
Tacos de birria Tijuana
Mulitas estilo frontera
Quesatacos estilo norte
Lonche de pierna Tijuana
Hot dog envuelto en bacon Tijuana
Papas locas frontera
Campechanas mariscos frontera
Mariscos Zona Norte
Sabores callejeros Tijuana
Ensalada César Tijuana origen
Guacamole en molcajete frontera
Burrito California papas fritas adentro
Burrito surf estilo Baja
Tacos de asada Tijuana
Tacos de tripa Tijuana
Tacos de lengua Tijuana
Tacos de cabeza Tijuana
Taco de vapor estilo norte
Trompo al pastor Tijuana
Al pastor en tortilla de harina Tijuana
Costillas BBQ frontera
Barbacoa de fin de semana Tijuana
Menudo domingo Tijuana
Pozole estilo frontera
Tostilocos Tijuana
Dorilocos estilo Tijuana
Corn dog bacon wrap Tijuana
Pizza gruesa estilo Tijuana
Hot dog TJ pierna y tocino
Sushi frontera fusion Baja
Mariscos Puerto Nuevo ruta Tijuana
Langosta mantequilla ruta Rosarito
Pescado zarandeado ruta Baja
Clamatos preparados Tijuana
Michelada cubeta Tijuana
Comida de Tijuana
Arepa ocañera
Almojábana ocañera
Pan aliñado ocañero
Mute santandereano ocaña
Mazamorra de maíz ocaña
Cabrito al horno ocaña
Seco de cabrito ocaña
Carne oreada ocañera
Empanada ocañera
Tamal ocañero
Chocolate de mesa ocañero
Cuajada con panela ocaña
Hormigas culonas tostadas Santander
Fresco de lulo ocaña
Fresco de borojó ocaña
Almibar de brevas ocaña
Dulce de leche cortado ocaña
Comida de Ocaña
Dulce tradicional colombiano
Arequipe casero
Brevas con arequipe
Manjar blanco antioqueño
Natilla navideña
Postre de tres leches
Merengón de guanábana
Merengón de maracuyá
Postre chajá estilo sur
Gelatina de pata
Cuajada con melao
Panelitas de leche
Bolegancho
Cocadas colombianas
Roscones rellenos
Torta negra colombiana
Obleas con arequipe
Almojábanas dulces
Mazamorra postre
Flan de coco colombiano
Galletas Festival dulce
Dulce tradicional mexicano
Jericalla jalisciense
Capirotada cuaresma
Calabaza en tacha
Camote en dulce
Ate de membrillo
Alegrías de amaranto
Cocada mexicana horneada
Polvorones de nuez
Nuez garapiñada
Glorias de leche
Muéganos dulces
Borrachitos rellenos
Rollo de guayaba
Galletas tipo María postre
Pan de muerto tradicional
Rosca de reyes
Colaciones campechanas
Garapiñado de cacahuate
Garapiñado de pepita
Jet wafer chocolate
Chocolatina Jet
Galletas Festival saladas
Papas Margarita limón
Papas Margarita natural
Papas Margarita BBQ
Papas Ruffles queso
Papas Sabritas adobadas
Takis fuego
Takis azules
Sabritas rancheritos
Cheetos Torciditos
Cheetos bolitas
Sabritones chile
Runners limón
Chicharrones de cerdo bolsa
Chicharrón de harina
Maní salado paquete
Maní japonés bolsa
Saladitos limón paquete
Pelón pelo rico
Pulparindo enchilado
Rockaleta enchilada
Bubulubu chocolate
Duvalín bi sabor
Carlos V chocolate
Galletas Cuétara tipo María
Galletas Emperador chocolate
Wafer chocolate paquete
Pastrami sandwich
TXT
            ),
        ))));
    }

    /**
     * @return list<string>
     */
    private static function drinks(): array
    {
        return array_values(array_unique(array_filter(array_map(
            'trim',
            explode("\n", <<<'TXT'
Café
Espresso
Latte
Cappuccino
Americano
Moka
Cold brew
Affogato
Macchiato
Flat white
Vino tinto
Vino blanco
Vino rosado
Champagne
Prosecco
Cava
Sangría
Clericot
Cerveza artesanal
Cerveza lager
Cerveza IPA
Michelada
Cubra libre
Mojito
Caipirinha
Caipiroska
Piña colada
Margarita
Mezcal
Tequila
Ron añejo
Whisky
Bourbon
Gin tonic
Vermut
Aguardiente
Chicha
Horchata
Atole
Chocolate caliente
Matcha latte
Smoothie
Jugo natural
Limonada
Agua de panela
Té helado
Kombucha
Sidra
Bebidas tradicionales
Bebida artesanal
Bebida fermentada
Bebida espirituosa suave
Coctelería clásica
Coctelería de autor
Mocktail sin alcohol
Mocktail frutal
Destilado artesanal
Licor de hierbas
Amaro digestivo
Digestivo de mesa
Aperitivo servido
Coctel refrescante
Infusión caliente aromática
Infusión fría
Agua fresca tradicional
Refresco de fruta natural
Mate cocido
Tereré frío
Chocolate en impacto
Pulque natural
Tepache casero
Ponche festivo
Colada espesa
Cerveza oscura
Cerveza sin alcohol
Sidra espumante
Vino natural
Vino ancestral
Vino naranja
Jerez y vinos generosos
Vino espumoso
Vino tinto Malbec
Vino tinto Cabernet Sauvignon
Vino tinto Carménère
Vino tinto Syrah
Vino tinto Merlot
Vino tinto Pinot Noir
Vino tinto Bonarda
Vino tinto Tannat
Vino tinto Tempranillo
Vino tinto Garnacha
Vino tinto Petit Verdot
Vino tinto Cabernet Franc
Vino tinto País
Vino blanco Chardonnay
Vino blanco Sauvignon Blanc
Vino blanco Torrontés
Vino blanco Viognier
Vino blanco Riesling
Vino blanco Chenin Blanc
Vino blanco Semillón
Vino blanco Moscatel
Vino blanco Verdejo
Vino blanco Albariño
Rosado de Garnacha
Rosado de Pinot Noir
Clarete
Cava brut
Champagne brut
Espumante brasileño
Espumante argentino brut
Vino joven sin barrica
Vino con crianza en roble
Vino reserva
Vino gran reserva
Vino seco
Vino semi-seco
Vino dulce natural
Perfil afrutado
Perfil herbáceo
Vino natural sin sulfitos
Vino del Valle de Guadalupe
Vino de Parras Coahuila
Vino de Querétaro
Vino de Guanajuato
Vino de Mendoza
Vino de Cafayate Salta
Vino del Valle de Uco
Vino de Maipo
Vino de Colchagua
Vino de Casablanca Chile
Vino del Valle del Itata
Vino de Canelones Uruguay
Vino de Serra Gaúcha
Vino colombiano Valle del Cauca
Vino colombiano Santander
Vino Rioja
Vino Ribera del Duero
Vino Chianti
Vino Barolo
Vino Porto ruby
Jerez fino
Oloroso
Pedro Ximénez
Sangría con vino tinto
Kalimotxo
Tinto de verano
Aguardiente con limón
Aguardiente con soda
Aguardiente sour
Refajo tradicional
Refajo con aguardiente
Canelazo tradicional
Canelazo con puntas
Chicha de maíz
Chicha de arroz
Guarapo con licor
Lulada con aguardiente
Viche
Cuba libre ron colombiano
Mojito ron colombiano
Daiquirí ron colombiano
Piña colada ron colombiano
Limonada de cereza con aguardiente
Tamarindo sour aguardiente
Sangría blanca con aguardiente
Carajillo ron colombiano
Margarita clásica
Margarita frozen
Margarita de mezcal
Margarita de tamarindo
Paloma
Paloma preparada
Paloma picante
Michelada oscura
Michelada cubana
Michelada con clamato
Tequila sunrise
Cantarito jalisciense
Batanga
Charro negro
Vampiro
Mezcal sour
Mezcal paloma
Mezcal negroni
Mezcalita de maracuyá
Mezcalita de mango
Oaxaca old fashioned
Tepache con mezcal
Tamarindo con mezcal
Carajillo
Carajillo Licor 43
Ponche navideño con piquete
Chilate con mezcal
Cuba libre tequila
Clericot mexicano
Sangría mexicana con tequila
Sangrita
Mojito de mezcal
Negroni clásico
Negroni sbagliato
Boulevardier
Old fashioned
Manhattan
Sazerac
Mai tai
Zombie
Planter’s punch
Blue Hawaii
Hurricane
Daiquirí clásico
Daiquirí de frutas
Mojito clásico
Mojito de maracuyá
Cuba libre clásico
Canchánchara
El Presidente
Pisco sour peruano
Pisco sour chileno
Chilcano
Chilcano de maracuyá
Ponche de habas
Ponche acachul
Cóctel algarrobina
Chilcano de coca
Chicha morada coctel
Chicha de jora
Chicha de guiñapo
Vino patero
Vino anaranjado natural
Pet-nat espumoso
Col fondo
Maceración carbónica
Vino en tinaja
Vino en ánfora
Vino de vaso joven
Vino clarete levantino
Vino de méthode ancestrale
Sidra natural basca
Sidra escanciada
Sidra ice cider
Perry de pera
Hidromiel artesanal
Cerveza saison
Cerveza farmhouse ale
Cerveza sour ale
Cerveza lambic
Cerveza gueuze
Cerveza kriek
Cerveza berliner weisse
Cerveza gose marino
Cerveza witbier
Cerveza tripel
Cerveza quadrupel
Cerveza barley wine
Cerveza imperial stout
Cerveza pastry stout
Cerveza cold ipa
Cerveza west coast ipa
Cerveza hazy ipa
Cerveza milk stout
Cerveza porter inglesa
Cerveza mild ale
Cerveza bitter inglesa
Cerveza kölsch
Cerveza altbier
Cerveza rauchbier
Cerveza dunkel
Cerveza helles
Cerveza märzen
Cerveza festbier
Cerveza schwarzbier
Cerveza bock doppel
Cerveza eisbock
Cerveza grodziskie
Cerveza grodziskie ahumada
Amaro montenegro
Amaro averna
Amaro nonino
Amaro lucano
Fernet branca
Fernet con cola
Fernet con soda
Strega
Chartreuse verde
Chartreuse amarillo
Bénédictine
Grand marnier
Cointreau
Triple sec artesanal
Curacao azul
Absenta tradicional
Pastis marsellesa
Ouzo griego
Rakija serbia
Slivovitz
Palinka húngara
Horalka
Becherovka
Unicum
Sambuca
Limoncello artesanal
Grappa nebbiolo
Pisco acholado
Singani boliviano
Cachaça envejecida
Cachaça prata
Cachaça ouro
Batida de coco
Batida de maracuyá
Coquito navideño
Ponche crema
Ponchecrema
Crema de vie cubana
Rom punch caribeño
Ti punch martiniqués
Planteur rhum
Swizzle bermudeño
Dark n stormy
Whisky sour clásico
New york sour
Whisky highball
Whisky ginger
Rob roy
Godfather
Rusty nail
Penicillin cocktail
Paper plane
Last word
Corpse reviver
Aviation
French 75
French martini
Espresso martini clásico
Carajillo frío
Carajillo caliente
Tinto de verano con limón
Tinto de verano con naranja
Tinto de verano con frutos rojos
Kalimotxo con limón
Refajo con limón
Michelada natural
Michelada roja
Michelada verde
Clamato preparado
Vampiro con clamato
Coctel bandera mexicana
Paloma de toronja rosa
Paloma de pomelo
Cantarito en jarrito
Cantarito de piña
Batanga con sal en borde
Sueros veracruzanos
Tuba fresca
Agua de coco natural
Coco loco caribeño
Sangría de vino blanco
Sangría de cava
Clericot de sidra
Clericot de espumante
Ponche de frutas colombiano
Canelazo de panela
Guarapo sin fermentar
Guarapo fermentado
Masato amazónico
Chicha de yuca
Chicha de quinua
Aguardiente con hierbas
Aguardiente doble anís
Champagne rosado brut
Franciacorta satèn
Trento doc
Crémant de Bourgogne
Crémant d Alsace
Espumante méthode traditionnelle
Vinho verde joven
Mateada compartida
Yerba mate fría tereré
Té matcha usucha
Té matcha koicha
Té oolong tostado
Té pu-erh
Té chai especiado
Té moruno
Agua de sales minerales
Agua tónica premium
Agua tónica light
Hibiscus tea helado
Limonada de hierbabuena
Limonada de jengibre
Jarrito de tamarindo natural
Refresco de guayaba
Licuado de mamey
Batido de lulo
Jugo de maracuyá natural
Jugo de guanábana
Jugo de tomate de árbol
Smoothie bowl acai
Cold pressed verde
Zumo mediterráneo
Gaseosa Colombiana Postobón
Gaseosa Manzana Postobón
Gaseosa Uva Postobón
Gaseosa Bretaña ginger ale
Gaseosa Bretaña tónica
Hit jugo de mango
Hit jugo de lulo
Hit jugo tropical
Quatro toronja
Sprite Colombia
Pepsi botella retornable
Coca-Cola vidrio frío
Malta Leona
Pony Malta
Mr té limón
Postobón manzana sin azúcar
Manzana Postobón en lata
Uva Postobón en botella
Colombiana en bandeja
Jarritos mandarina
Jarritos tamarindo
Jarritos toronja
Jarritos limón
Jarritos piña
Jarritos guayaba
Sidral Mundet manzana
Sangría Señorial refresco
Lift manzana
Yoli grapefruit
Mundet sidral tradicional
Refresco Barrilitos
Dr Pepper México
Fanta naranja México
Escuis hierbas
Peñafiel mineral preparada
Refresco de cola estilo México
Tónica Penafiel
Sueroxy tipo hidratante
Gatorade preparado frío
Clamato preparado botella
Vampiro preparado industrial
Trago casero chicha de maíz
Trago casero guarapo panelero
Trago casero masato yuca
Trago casero masato arroz
Trago casero tepache piña
Trago casero tepache de frutas
Colonche de tuna casero
Trago casero rompope casero
Trago casero ponche navideño
Trago casero ponche de frutas
Trago casero colado espeso
Trago casero limonada panela
Trago casero agua panela con limón
Trago casero horchata arroz
Trago casero jamaica concentrada
Trago casero tamarindo natural
Trago casero maracuyá diluido
Trago casero viche panelero
Trago casero mistela casera
Trago casero licor de hierbas casero
Trago casero coctel de fruta sin alcohol
Trago casero refresco de panela con clavo
Trago casero aguardiente en maceración de fruta
Trago casero vino de frutilla casero
Trago casero café frío panela
Trago casero chocolate espeso taza
Trago casero atol agrio
Trago casero pinolillo
Trago casero fresco de maíz nixtamal
Espresso martini tequila
TXT
            ),
        ))));
    }

    /**
     * @return list<string>
     */
    private static function experiences(): array
    {
        return array_values(array_unique(array_filter(array_map(
            'trim',
            explode("\n", <<<'TXT'
Tradicional
Gourmet
Callejero
Familiar
Romántico
Celebración
Negocios
Brunch
Desayuno de trabajo
Cena de autor
Degustación
EcoAnálisis
Terraza
Vista panorámica
Música en vivo
Pet friendly
Niños bienvenidos
Picnic
Delivery premium
Experiencia chef en casa
Temático vintage
Saludable
Vegano
Vegetariano
Sin gluten
Picante extremo
Comfort food
Late night
After office
Happy hour
Fiesta patronal
Ritual festivo comunitario
Sobremesa prolongada
Lonche meridiano
Merienda vespertina
Once tradicional
Desayuno relajado
Almuerzo familiar
Cena íntima
Cena social amplia
Consumo en madrugada
Mesa compartida larga
Cotidianidad local
Viaje sostenible
Descubrimiento de sabores
Nostalgia culinaria
Orgullo regional
Intercambio cultural
Presupuesto accesible
Gama media en mesa
Experiencia alta gama
Celebración íntima
Banquete numeroso
Salida espontánea entre amigos
Terraza al atardecer
Mercado sostenible
Pop-up efímero
Bar de barrio
Taberna tradicional
Rituales religiosos en mesa
Reunión intergeneracional
Sunset en rooftop
Almuerzo dominical largo
Domingo de tamal
Domingo de asado
Comida de empresa ligera
Coffee break extendido
Networking culinario
Primer encuentro romántico
Segunda cita en mesa
Aniversario en pareja
Cena de compromiso
Propuesta en iniciativa
Celebración de grado
Comunión o bautizo
Quince años o fiesta
Boda civil íntima
Boda con banquete
Cumpleaños con menú
Cumpleaños sorpresa
Reunión de exalumnos
Tertulia con vino
Cata privada
Cata pública
Clase de cocina grupal
Taller de coctelería
Show cooking en vivo
Menú degustación estacional
Menú EcoAnálisis con sommelier
Ruta de tapas
Craw de cerveza artesanal
Craw de bares
Festival sostenible
Feria de práctica callejera
Día de mercado húmedo
Paseo de food trucks
Comida en finca
Comida en viñedo
Comida en bodega
Cosecha y asado
Asado de amigos
Parrillada de fin de semana
Noche de juegos y botanas
Cine y cena
Teatro y cena
Museo y cafetería
Lectura y cafetería
Trabajo remoto en cafetería
Despedida de soltero moderada
Despedida de soltera brunch
Primera comunión familiar
Encuentro de vecinos
Club de lectura y té
Partido y snacks
Karaoke con botanas
Subasta benéfica con cena
Gala con servicio de gala
Sala privada en iniciativa
Reserva de chef en mesa
Menú sin reservas walk-in
Colación antes de evento
Colación después de evento
Cena bajo las estrellas
Ocasiones especiales
Navidad en familia
Cena de Nochebuena
Cena de Año Nuevo
Brunch de Año Nuevo
Día de Muertos pan y chocolate
Ofrenda Día de Muertos
Semana Santa ayuno y vigilia
Semana Santa dulces tradición
Posadas navideñas México
Novenario sostenible
San Valentín cena pareja
Día de la Madre desayuno
Día del Padre parrillada
Día del Niño dulces y piñata
Graduación universitaria banquete
Graduación secundaria festejo
Primera comunión banquete
Confirmación religiosa práctica
Boda civil recepción
Boda religiosa banquete
Aniversario de bodas formal
Quinceañera banquete
Bautizo familiar práctica
Funeral recepción respetuosa
Inauguración de negocio cóctel
Cierre de año empresa
Anfitrión en casa
TXT
            ),
        ))));
    }
}
