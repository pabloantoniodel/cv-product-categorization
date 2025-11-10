<?php
declare(strict_types=1);

namespace Cv\ProductCategorization\Processors;

use WP_CLI;

/**
 * Porta la lógica de `vaciar-otros-smart.php` para ejecutarla desde el plugin.
 */
final class OtrosCleaner
{
    private bool $apply;

    public function __construct(bool $apply = false)
    {
        $this->apply = $apply;
    }

    public function run(int $offset = 0, int $limit = 200): void
    {
        [$OTROS, $VARIOS] = $this->resolveOtrosTerms();

        [
            $BEL, $PEL, $COS, $MANI, $DEP, $TAT,
            $MODA, $MODA_M, $MODA_H, $MODA_I, $LEN, $DESC, $BANO, $ZAPA, $ACCES,
            $JOY_MAIN,
            $ALIM, $BEB, $PLAT, $FRES, $PAN, $DULCES,
            $MASC, $MASC_ALI, $MASC_ACC,
            $HOG, $TEXTIL, $MUEB, $ILUM, $DECO, $EXTER, $LIMPIEZA_HOGAR, $JARDIN, $MENAJE,
            $SERV, $FOTO, $IMPRE, $REP, $CONS, $FORM, $PROMO, $LOTO, $LIMP, $PAP, $ESOT, $CERT, $CERR, $EVENTOS, $SEGURIDAD, $FINANZAS, $SOLAR, $TRANS, $INDUS, $REP_COM,
            $BEBE, $PROD_BEBE, $JUGUETES,
            $REG, $FUMADOR,
            $TEC, $TEC_MOVIL, $TEC_SERV, $TEC_ELEC,
            $INMO, $VENTA_TERM, $ALQ_TERM, $TRASP_TERM,
            $DEPORTE, $DEP_TIENDAS, $DEP_PESCA,
            $VEH, $VEH_VENTA, $VEH_SERV,
            $SALUD, $PARAF,
            $JARD, $JARD_FERT, $JARD_CUIDADO
        ] = $this->prepareCategoryIds();

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'post_status'    => 'publish',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $OTROS,
                ],
            ],
        ];

        $posts = get_posts($args);
        if (!$posts) {
            $this->log('✅ No hay más productos en Otros');
            return;
        }

        $this->log(sprintf(
            "🧹 VACIAR OTROS\nOffset: %d  Límite: %d  Modo: %s\n",
            $offset,
            $limit,
            $this->apply ? '✅ APLICAR' : '⚠️  PRUEBA'
        ));

        $changed = 0;
        $kept    = 0;

        foreach ($posts as $p) {
            $title   = $this->norm($p->post_title);
            $excerpt = $this->norm($p->post_excerpt);
            $text    = $title . ' ' . $excerpt;
            $curr    = wp_get_post_terms($p->ID, 'product_cat', ['fields' => 'ids']);

            if (is_wp_error($curr)) {
                $this->warn(sprintf('No se pudieron obtener términos de #%d: %s', $p->ID, $curr->get_error_message()));
                continue;
            }

            $proposed = [];

            // --- Belleza ---
            if (preg_match('/\b(peinado|recogid[oa]s?|corte (pelo|cabello|caballero)|tinte|mechas?|keratina|alisado|alisamiento|barber|reflejos?\s+melena|pulido\s+normal)\b/u', $text)) {
                $proposed[] = $BEL;
                $proposed[] = $PEL;
            }
            if (preg_match('/\b(manicura|pedicura|unas|semiperm[a]?nente|acrilico|gel)\b/u', $text)) {
                $proposed[] = $BEL;
                $proposed[] = $MANI;
            }
            if (preg_match('/\b(perfumes?|aromas?|cosmetic|champu?s?|cremas?|maquillajes?|serum|hidratantes?|rimel|labial(?:es)?|pintalabios|coloracion|tulipan\s+negro)\b/u', $text)) {
                $proposed[] = $BEL;
                $proposed[] = $COS;
            }
            if (preg_match('/\b(peluca(s)?|trenzas?\s+brasile(n|ñ)as?)\b/u', $text)) {
                $proposed[] = $BEL;
                $proposed[] = $PEL;
            }
            if (preg_match('/\b(cabell[oa]s?|cabello)\b/u', $text)) {
                $proposed[] = $BEL;
                $proposed[] = $PEL;
            }
            if (preg_match('/\b(peeling|rulos?)\b/u', $text)) {
                $proposed[] = $BEL;
                $proposed[] = $PEL;
            }
            if (preg_match('/\b(tatuajes?|tattoo|tatoo)\b/u', $text)) {
                $proposed[] = $BEL;
                $proposed[] = $TAT;
            }
            if (preg_match('/\b(certificad(o|a)s?\s+energetic(o|a)?|cee)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $CERT;
            }

            // --- Moda ---
            if (preg_match('/\b(ba(n|ñ)ador(?:es)?|bikini(?:s)?|trajes?\s+de\s+ba(n|ñ)o)\b/u', $text)) {
                $proposed[] = $MODA;
                $proposed[] = $BANO;
            }
            if (preg_match('/\b(sujetador(?:es)?|braga|lencer)\b/u', $text)) {
                $proposed[] = $MODA;
                $proposed[] = $LEN;
            }

            $genderTerm = null;
            if (preg_match('/\b(caballer|hombre|masculin)\b/u', $text)) {
                $genderTerm = $MODA_H;
            } elseif (preg_match('/\b(mujer|se(n|ñ)ora|dama|femenin)\b/u', $text)) {
                $genderTerm = $MODA_M;
            } elseif (preg_match('/\b(niñ|peque|bebe|bebé|infantil)\b/u', $text)) {
                $genderTerm = $MODA_I;
            }

            if (preg_match('/(zapatill[as]?|zapatos?|bot(a|i)n(?:es)?|botas?|sandalias?|calzado[s]?|chanclas?|deportiv[ao]s?|biomecanics|zueco(s)?|ballenero)/u', $text)) {
                $proposed[] = $MODA;
                $proposed[] = $ZAPA;
                if ($genderTerm) {
                    $proposed[] = $genderTerm;
                }
            }
            if (preg_match('/\b(gafas?\s+de\s+sol|gafas?\s+opticas?|gafas?\s+graduadas?|montura(s)?|ray[\s-]*ban|sun\s*gla(?:ss|sses)?|sunglass(?:es)?|lentes?\s+de\s+(sol|vista))\b/u', $text)) {
                $proposed[] = $MODA;
                $proposed[] = $ACCES;
                if ($genderTerm) {
                    $proposed[] = $genderTerm;
                }
            }
            if (preg_match('/(camiset[ao]s?|camisa[s]?|blusa[s]?|polo[s]?|falda[s]?|vestid[oa]s?|pantalon(?:es)?|pantalones|pijama[s]?|faja[s]?|chaqueta[s]?|abrigo[s]?|ropa\\s+interior|sudadera[s]?|jersey(s)?|bolso[s]?|mochila[s]?|legging(s)?|leggins?|mono[s]?|chaleco[s]?|conjunto[s]?|arregl[oa]s?|pa[nñ]uelo(s)?|hannibal\\s+laguna|chandal(?:es)?|albornoz(?:es)?|gabardina(s)?|slip(s)?|braguita(s)?|sueter(es)?|cazadora(s)?)/u', $text)) {
                $proposed[] = $MODA;
                if ($genderTerm) {
                    $proposed[] = $genderTerm;
                } else {
                    $proposed[] = $MODA_M;
                }
            }

            if (preg_match('/\b(patuco(s)?|peke|bebe)\b/u', $text)) {
                $proposed[] = $BEBE;
                $proposed[] = $PROD_BEBE;
            }
            if (preg_match('/\b(doudou|babero[s]?|chupete[s]?|mantita)\b/u', $text)) {
                $proposed[] = $BEBE;
                $proposed[] = $PROD_BEBE;
            }
            if (preg_match('/\b(dudu|atrapasue[nñ]os|baby\b|albornoz)\b/u', $text)) {
                $proposed[] = $BEBE;
                $proposed[] = $PROD_BEBE;
            }
            if (preg_match('/\b(juguete|peluche|juego|minicoche)\b/u', $text)) {
                $proposed[] = $BEBE;
                $proposed[] = $JUGUETES;
            }

            // --- Joyería ---
            if (preg_match('/\b(anillo(s)?|alianza(s)?|collar(es)?|pulsera(s)?|pendiente(s)?|colgante(s)?|reloj(es)?)\b/u', $text)) {
                if ($JOY_MAIN) {
                    $proposed[] = $JOY_MAIN;
                }
            }

            // --- Alimentación ---
            if (preg_match('/\b(couscous|desayuno|pica\s*pica|pitas|menu|bocadillo|cocadillo|sandwich|arepa(s)?|empanad(as?)?|empanadilla(s)?|samosa|alitas|hamburguesa|paella|caterin|banquete|chopitos|mejillon(?:es)?|mejillones?|arroz\b.*bogavante|arroz\s+a\s*banda|arroz\s+cardoso|arroz\s+negro|ensaladilla|ensalada|plato(s)?\s+combinado(s)?|servicio de comida|coca(s)?|ensaimad(as?)?|fideu[aà]|tortilla|perrito\s+caliente|pulpo\s+a\s+la\s+gallega|patata\s+con\s+higado|patatas?\s+bravas|boquerones?\s+fritos?|croquetas?|tabla\s+de\s+iberic(?:o|os)?|burrito|queso\s+empanado|huevo(s)?\s+frito(s)?|huevos\s+con\s+patatas|gambas?|pakora|nachos|cochinillo|lomo\s+adobado|lomo\s+con\s+patatas|costillas?|codillo|bacalao\s+rebozado|pinchitos?|lubina|tocino\s+de\s+cielo|medianoche|spaghetti|pabellon|papa\s+rellena|tequenos|mentiroso)\b/u', $text)) {
                $proposed[] = $ALIM;
                $proposed[] = $PLAT;
            }
            if (preg_match('/\b(naranja(s)?|miel(es)?|carne|chuleton|albondigas?|magro|bogavante|mejillon(?:es)?|chorizo|fritura|cerdo|cordero|pollo|tomate(s)?|aceite|pisto|ketchup|platano(s)?|kiwi(s)?|jam[oó]n|rabo\\s+de\\s+toro|gamb[oó]n|pastas?\\s+caseras?|ciruela(s)?|pera(s)?|lim[oó]n(?:es)?|higo(s)?|sand[ií]a|fresa(s)?|cereza(s)?|esparragos?|vinagre|melocoton(?:es)?|calabaza(s)?|sardina(s)?)\b/u', $text)) {
                $proposed[] = $ALIM;
                $proposed[] = $FRES;
            }
            if (preg_match('/\b(croissa?nt(?:es|s)?|croasa?nt?(?:es|s)?|brazo\\s+gitano|bazo\\s+gitano|tart(a|e)(s)?|magdalena(s)?|baguette|macarron(?:es|s)?|dumru)\b/u', $text)) {
                $proposed[] = $ALIM;
                $proposed[] = $PAN;
            }
            if (preg_match('/\b(chuch(es)?|chicle|chupa\s*chup(s)?|dulce|surtido|oblea|snack|gominola|kinder|chocolate|gofre|cucurucho|macarron(?:es|s)?|tarta(s)?|helado[s]?|galleta(s)?|regaliz)\b/u', $text)) {
                $proposed[] = $ALIM;
                $proposed[] = $DULCES;
            }
            if (preg_match('/\b(bebida|licor|vino|cerveza|cerbeza|refresco|cafe|caf(e|é)|cortado|whisk[eiy]?|whisky|infusi[oó]n(?:es)?|coca-?cola|ginebra|ron|vodka|coctel|c[oó]ctel|coktail|jugo\s+de\s+hierbas?|azul\s+cielito\s+lindo|variedad\s+en\s+t[eé])\b/u', $text)) {
                $proposed[] = $ALIM;
                $proposed[] = $BEB;
            }

            // --- Mascotas ---
            if (preg_match('/\b(perr|gat|mascot)\b/u', $text) && preg_match('/\b(pienso|comida|alimento|latas?|pate|pat(e|é)|snacks?|premios?)\b/u', $text)) {
                $proposed[] = $MASC;
                $proposed[] = $MASC_ALI;
            }
            if (preg_match('/\bcama(s)?(?:\s+\w+){0,3}\s+para\s+(perr[a-z]*|gat[oa]s?|mascotas?)\b/u', $text)) {
                $proposed[] = $MASC;
                $proposed[] = $MASC_ACC;
            }

            // --- Hogar ---
            if (preg_match('/\b(cortina(s)?|tela(s)?|colchon(es)?|toalla(s)?|toallon(es)?|cojin(es)?|cojin personalizado|textil)\b/u', $text)) {
                $proposed[] = $HOG;
                $proposed[] = $TEXTIL;
            }
            if (preg_match('/\b(ambientador(?:es)?|aromatizador(?:es)?|ramo(s)?|flor(es)?|floral|rosa(s)?|taza(s)?|busto(s)?|figura(s)?|decoracion|decorativo)\b/u', $text)) {
                $proposed[] = $HOG;
                $proposed[] = $DECO;
            }
            if (preg_match('/\b(toldo(s)?|cofre\s+ares|enrollable|screen|estor(es)?)\b/u', $text)) {
                $proposed[] = $HOG;
                $proposed[] = $EXTER;
            }
            if (preg_match('/\b(limpiador(?:es)?|multiusos|desinfectante|detergente|lejia|limpieza|fairy)\b/u', $text)) {
                $proposed[] = $HOG;
                $proposed[] = $LIMPIEZA_HOGAR;
            }
            if (preg_match('/\b(lampara(s)?|iluminacion|foco(s)?)\b/u', $text)) {
                $proposed[] = $HOG;
                $proposed[] = $ILUM;
            }
            if (preg_match('/\b(mueble(s)?|sofa(s)?|sillon(es)?|mesa(s)?|butaca(s)?)\b/u', $text)) {
                $proposed[] = $HOG;
                $proposed[] = $MUEB;
            }

            // --- Servicios ---
            if (preg_match('/\b(foto(s)?\s+\d+\s*(x|por)\s*\d+|foto(s)?.*comunion|fotograf|reportaje)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $FOTO;
            }
            if (preg_match('/\b(clases|curso(s)?|formacion|matricula|academia|educacion|taller(es)?)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $FORM;
            }
            if (preg_match('/\b(lavado|lavanderia|edredon|camiseras|limpieza en seco)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $LIMP;
            }
            if (preg_match('/\b(certificado energetico|cee|certificacion energetica)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $CERT;
            }
            if (preg_match('/\b(cerrajer|copia(s)? de llaves|llavero(s)?|duplicado de llave)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $CERR;
            }
            if (preg_match('/\b(evento(s)?|graduacion|celebracion|banquete|comunion)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $EVENTOS;
            }
            if (preg_match('/\b(ingresos pasivos|multinivel|network marketing|crypto|bitcoin|blockchain|trading)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $FINANZAS;
            }
            if (preg_match('/\b(placas solares|autoconsumo solar|fotovoltaico)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $SOLAR;
            }
            if (preg_match('/\b(transporte de pasajeros|alquiler de sillas electricas|servicio de transporte|alquiler de bicicletas|mensajeria)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $TRANS;
            }

            // --- Papelería ---
            if (preg_match('/\b(folios?|impresion|fotocopia|tarjetas? de visita|bolsa(s)? (de )?carton|papeleria)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $PAP;
            }

            // --- Esoterismo ---
            if (preg_match('/\b(tarot|videncia|esoter|astrolog)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $ESOT;
            }

            // --- Seguridad ---
            if (preg_match('/\b(caja(s) de seguridad|vigilancia|alarmas?)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $SEGURIDAD;
            }

            // --- Industrial ---
            if (preg_match('/\b(material industrial|tubos de canalizacion|canalizacion|saneamiento)\b/u', $text)) {
                $proposed[] = $SERV;
                $proposed[] = $INDUS;
            }

            if (preg_match('/\b(agente comercial|representante comercial|representaci[oó]n comercial|delegad[oa] comercial|broker comercial|outsourcing comercial|fuerza de ventas|captaci[oó]n de clientes|gesti[oó]n comercial)\b/u', $text)) {
                $proposed[] = $SERV;
                if ($REP_COM) {
                    $proposed[] = $REP_COM;
                }
            }

            // --- Salud ---
            if (preg_match('/\b(lipo slim|suplemento|vitamin|nutricion|bienestar|protector solar|leche protectora|masaje|spa|liquido lentes de contacto|colirio)\b/u', $text)) {
                $proposed[] = $SALUD;
                $proposed[] = $PARAF;
            }

            // --- Jardinería ---
            if (preg_match('/\b(fertilizante|sustrato|abono)\b/u', $text)) {
                $proposed[] = $JARD;
                $proposed[] = $JARD_FERT;
            }
            if (preg_match('/\b(jardin|planta|cuidados de plantas|bonsai)\b/u', $text)) {
                $proposed[] = $JARD;
                $proposed[] = $JARD_CUIDADO;
            }

            // --- Tecnología ---
            if (preg_match('/\b(accesorio(s)? para (movil|móvil)|smartphone|iphone|tablet|pantalla|reparacion movil|servicio web|hosting|seo|marketing digital|tienda online|tienda virtual|diseño web|televisor|electrodomestico|gadget|microfono)\b/u', $text)) {
                $proposed[] = $TEC;
                $proposed[] = $TEC_SERV;
            }
            if (preg_match('/\b(reparacion movil|pantalla movil|iphone|samsung|xiaomi)\b/u', $text)) {
                $proposed[] = $TEC;
                $proposed[] = $TEC_MOVIL;
            }
            if (preg_match('/\b(lavadora|lavavajillas|microondas|secadora|nevera|frigorifico|televisor|gadget|minipimer|electrodomestico)\b/u', $text)) {
                $proposed[] = $TEC;
                $proposed[] = $TEC_ELEC;
            }

            // --- Regalos ---
            if (preg_match('/\b(souvenir|regalo|detalle|muñeco|llavero|cinturon|delantal|bolsa con fotografia|portamaletas|maleta|pipa|shisha|cachimba|vape|encendedor|mechero|funkos? pop|letra preservada)\b/u', $text)) {
                $proposed[] = $REG;
                $proposed[] = $FUMADOR;
            }

            $proposed = array_values(array_filter(array_unique($proposed)));

            $preserve = array_values(array_diff($curr, [$OTROS, $VARIOS]));

            $final = [];

            if (!empty($proposed)) {
                $final = array_slice(array_values(array_diff(array_unique(array_merge($preserve, $proposed)), [$OTROS, $VARIOS])), 0, 5);
            } else {
                if (!empty($preserve) && count($preserve) !== count($curr)) {
                    $final = array_slice(array_values(array_unique($preserve)), 0, 5);
                } else {
                    $kept++;
                    continue;
                }
            }

            sort($final);
            sort($curr);

            if ($final === $curr) {
                $kept++;
                continue;
            }

            $before = implode(', ', $this->resolveTermNames($curr));
            $after  = implode(', ', $this->resolveTermNames($final));

            $this->log(sprintf('🔄 #%d: %s', $p->ID, mb_strlen($p->post_title) > 70 ? mb_substr($p->post_title, 0, 67) . '...' : $p->post_title));
            $this->log('   Antes: ' . $before);
            $this->log('   Después: ' . $after);

            if ($this->apply) {
                $result = wp_set_post_terms($p->ID, $final, 'product_cat');
                if (is_wp_error($result)) {
                    $this->warn('   ⚠️  Error al aplicar: ' . $result->get_error_message());
                } else {
                    $this->log('   ✅ APLICADO');
                }
            } else {
                $this->log('   ⚠️  PRUEBA');
            }

            $this->log('');
            $changed++;
        }

        $this->log(sprintf("Resumen: Cambiados %d | Sin cambios %d\n", $changed, $kept));
    }

    /**
     * @return array<int,int|null>
     */
    private function resolveOtrosTerms(): array
    {
        $OTROS = $this->getTermIdByName('Otros Productos y Servicios') ?? 759;
        $VARIOS = $this->getTermIdByName('Varios') ?? 828;
        return [$OTROS, $VARIOS];
    }

    /**
     * Replica las asignaciones de IDs del script original.
     *
     * @return array<int,int|null>
     */
    private function prepareCategoryIds(): array
    {
        $BEL = $this->getTermIdByName('Belleza y Estética') ?? 748;
        $PEL = $this->ensureTerm('Peluquería', $BEL);
        $COS = $this->ensureTerm('Cosmética y Perfumería', $BEL);
        $MANI = $this->ensureTerm('Manicura y Pedicura', $BEL);
        $DEP = $this->ensureTerm('Depilación y Tratamientos', $BEL);
        $TAT = $this->ensureTerm('Tatuajes y Piercings', $BEL);

        $MODA = $this->getTermIdByName('Moda y Calzado') ?? 747;
        $MODA_M = $this->ensureTerm('Moda Mujer', $MODA);
        $MODA_H = $this->ensureTerm('Moda Hombre', $MODA);
        $MODA_I = $this->ensureTerm('Moda Infantil', $MODA);
        $LEN = $this->ensureTerm('Lencería', $MODA);
        $DESC = $this->ensureTerm('Pijamas y Ropa de Descanso', $MODA);
        $BANO = $this->ensureTerm('Baño y Playa', $MODA);
        $ZAPA = $this->ensureTerm('Zapatería', $MODA);
        $ACCES = $this->ensureTerm('Complementos de Moda', $MODA);

        $JOY_MAIN = $this->getTermIdByName('Joyería y Relojes');

        $ALIM = $this->getTermIdByName('Alimentación y Restauración') ?? 746;
        $BEB = $this->ensureTerm('Bebidas', $ALIM);
        $PLAT = $this->ensureTerm('Platos Preparados', $ALIM);
        $FRES = $this->ensureTerm('Productos Frescos', $ALIM);
        $PAN = $this->ensureTerm('Panadería y Pastelería', $ALIM);
        $DULCES = $this->ensureTerm('Dulces y Snacks', $ALIM);

        $MASC = $this->getTermIdByName('Mascotas') ?? 755;
        $MASC_ALI = $this->ensureTerm('Alimentación Mascotas', $MASC);
        $MASC_ACC = $this->ensureTerm('Accesorios Mascotas', $MASC);

        $HOG = $this->getTermIdByName('Hogar y Decoración') ?? 749;
        $TEXTIL = $this->ensureTerm('Textil Hogar', $HOG);
        $MUEB = $this->ensureTerm('Muebles', $HOG);
        $ILUM = $this->ensureTerm('Iluminación', $HOG);
        $DECO = $this->ensureTerm('Decoración', $HOG);
        $EXTER = $this->ensureTerm('Exterior y Toldos', $HOG);
        $LIMPIEZA_HOGAR = $this->ensureTerm('Limpieza del Hogar', $HOG);
        $JARDIN = $this->ensureTerm('Jardinería y Plantas', $HOG);
        $MENAJE = $this->ensureTerm('Menaje de Cocina', $HOG);

        $SERV = $this->getTermIdByName('Servicios Profesionales') ?? 752;
        $FOTO = $this->ensureTerm('Fotografía y Vídeo', $SERV);
        $IMPRE = $this->ensureTerm('Imprenta y Rotulación', $SERV);
        $REP = $this->ensureTerm('Reparaciones del Hogar', $SERV);
        $CONS = $this->ensureTerm('Consultoría y Ayudas', $SERV);
        $FORM = $this->ensureTerm('Formación y Educación', $SERV);
        $PROMO = $this->ensureTerm('Promociones y Campañas', $SERV);
        $LOTO = $this->ensureTerm('Loterías y Juegos', $SERV);
        $LIMP = $this->ensureTerm('Servicios de Limpieza', $SERV);
        $PAP = $this->ensureTerm('Papelería y Manualidades', $SERV);
        $ESOT = $this->ensureTerm('Tarot y Esoterismo', $SERV);
        $CERT = $this->ensureTerm('Certificados Energéticos', $SERV);
        $CERR = $this->ensureTerm('Cerrajería y Copias de Llaves', $SERV);
        $EVENTOS = $this->ensureTerm('Eventos y Celebraciones', $SERV);
        $SEGURIDAD = $this->ensureTerm('Seguridad y Vigilancia', $SERV);
        $FINANZAS = $this->ensureTerm('Inversiones y Finanzas', $SERV);
        $SOLAR = $this->ensureTerm('Energía Solar', $SERV);
        $TRANS = $this->ensureTerm('Transporte y Mensajería', $SERV);
        $INDUS = $this->ensureTerm('Material Industrial', $SERV);
        $REP_COM = $this->ensureTerm('Representación Comercial', $SERV);

        $BEBE = $this->getTermIdByName('Bebé e Infantil') ?? 754;
        $PROD_BEBE = $this->ensureTerm('Productos Bebé', $BEBE);
        $JUGUETES = $this->ensureTerm('Juguetes y Ocio Infantil', $BEBE);

        $REG = $this->ensureTerm('Regalos y Complementos', 0);
        $FUMADOR = $this->ensureTerm('Accesorios para Fumadores', $REG);

        $TEC = $this->getTermIdByName('Tecnología e Informática');
        if (!$TEC) {
            $TEC = $this->getTermIdByName('Tecnología y Electrónica');
        }
        if (!$TEC) {
            $TEC = $this->ensureTerm('Tecnología y Electrónica', 0);
        }
        $TEC_MOVIL = $this->ensureTerm('Accesorios y Reparación Móvil', $TEC);
        $TEC_SERV = $this->ensureTerm('Servicios Web y Digitales', $TEC);
        $TEC_ELEC = $this->ensureTerm('Electrodomésticos y Gadgets', $TEC);

        $INMO = $this->getTermIdByName('Inmobiliaria');
        $VENTA_TERM = $this->getTermIdByName('Venta');
        $ALQ_TERM = $this->getTermIdByName('Alquiler');
        $TRASP_TERM = $this->getTermIdByName('Traspaso');

        $DEPORTE = $this->getTermIdByName('Deporte y Fitness') ?? $this->ensureTerm('Deporte y Fitness', 0);
        $DEP_TIENDAS = $this->ensureTerm('Tiendas de Deporte', $DEPORTE);
        $DEP_PESCA = $this->ensureTerm('Pesca y Outdoor', $DEPORTE);

        $VEH = $this->getTermIdByName('Vehículos y Motor') ?? $this->ensureTerm('Vehículos y Motor', 0);
        $VEH_VENTA = $this->ensureTerm('Venta de Vehículos', $VEH);
        $VEH_SERV = $this->ensureTerm('Servicios y Mantenimiento', $VEH);

        $SALUD = $this->getTermIdByName('Salud y Bienestar') ?? $this->ensureTerm('Salud y Bienestar', 0);
        $PARAF = $this->ensureTerm('Parafarmacia', $SALUD);

        $JARD = $this->getTermIdByName('Jardinería y Plantas') ?? $this->ensureTerm('Jardinería y Plantas', 0);
        $JARD_FERT = $this->ensureTerm('Fertilizantes y Sustratos', $JARD);
        $JARD_CUIDADO = $this->ensureTerm('Cuidados de Plantas', $JARD);

        return [
            $BEL, $PEL, $COS, $MANI, $DEP, $TAT,
            $MODA, $MODA_M, $MODA_H, $MODA_I, $LEN, $DESC, $BANO, $ZAPA, $ACCES,
            $JOY_MAIN,
            $ALIM, $BEB, $PLAT, $FRES, $PAN, $DULCES,
            $MASC, $MASC_ALI, $MASC_ACC,
            $HOG, $TEXTIL, $MUEB, $ILUM, $DECO, $EXTER, $LIMPIEZA_HOGAR, $JARDIN, $MENAJE,
            $SERV, $FOTO, $IMPRE, $REP, $CONS, $FORM, $PROMO, $LOTO, $LIMP, $PAP, $ESOT, $CERT, $CERR, $EVENTOS, $SEGURIDAD, $FINANZAS, $SOLAR, $TRANS, $INDUS, $REP_COM,
            $BEBE, $PROD_BEBE, $JUGUETES,
            $REG, $FUMADOR,
            $TEC, $TEC_MOVIL, $TEC_SERV, $TEC_ELEC,
            $INMO, $VENTA_TERM, $ALQ_TERM, $TRASP_TERM,
            $DEPORTE, $DEP_TIENDAS, $DEP_PESCA,
            $VEH, $VEH_VENTA, $VEH_SERV,
            $SALUD, $PARAF,
            $JARD, $JARD_FERT, $JARD_CUIDADO,
        ];
    }

    private function norm(?string $input): string
    {
        if ($input === null) {
            $input = '';
        }

        if (!function_exists('remove_accents')) {
            require_once ABSPATH . 'wp-includes/formatting.php';
        }

        $decoded = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean   = remove_accents($decoded);
        $lower   = mb_strtolower($clean, 'UTF-8');
        return preg_replace('/\s+/u', ' ', trim($lower));
    }

    private function getTermIdByName(string $name): ?int
    {
        $term = get_term_by('name', $name, 'product_cat');
        return $term ? (int) $term->term_id : null;
    }

    private function ensureTerm(string $name, ?int $parent): ?int
    {
        if ($parent === null) {
            return null;
        }

        $term = get_term_by('name', $name, 'product_cat');
        if ($term && (int) $term->parent === (int) $parent) {
            return (int) $term->term_id;
        }

        $result = wp_insert_term($name, 'product_cat', ['parent' => $parent]);
        if (is_wp_error($result)) {
            $existing = $result->get_error_data('term_exists');
            return $existing ? (int) $existing : null;
        }

        return (int) $result['term_id'];
    }

    /**
     * @param array<int,int> $termIds
     * @return array<int,string>
     */
    private function resolveTermNames(array $termIds): array
    {
        $names = [];
        foreach ($termIds as $termId) {
            $term = get_term($termId, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $names[] = $term->name;
            }
        }
        return $names;
    }

    private function log(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($message);
        } else {
            echo $message . PHP_EOL;
        }
    }

    private function warn(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::warning($message);
        } else {
            fwrite(STDERR, $message . PHP_EOL);
        }
    }
}
