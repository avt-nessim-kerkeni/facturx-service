#[Route(path: '/mouvement/print/{mouvement_id}/{mode}', name: 'mouvement_print')]
    #[Route(path: '/cli/mouvement/print/{mouvement_id}/{mode}', name: 'cli_mouvement_print')]
    public function printMouvementAction(Pdf $knpSnappyPdf, $mouvement_id, $mode = 'D')
    {
        $em = $this->doctrine->getManager();
        $myFct = new MyFct();
        $mouvement_id = (int) $mouvement_id;
        $mouvement = $em->getRepository(Mouvement::class)->find($mouvement_id);
        $typeMouvement = $mouvement->getTypeMouvement();
        $type = strtoupper($typeMouvement->getCode());
        $filiale = $typeMouvement->getFiliale();
        $logo = '/logo/filiale/' . $filiale->getLogo();
        //new
        $piedDePageMouvement = $filiale->getPiedDePageMouvement();
        $piedDePageMouvement = str_replace('<o:p></o:p>', '', $piedDePageMouvement);
        $piedDePageMouvement = str_replace('.', '
', $piedDePageMouvement);

        $dateMouvement = $mouvement->getDateMouvement();
        $dateMouvement = $dateMouvement->format('d/m/Y');
        $client = $mouvement->getClient();
        $clientFacturation = $client->getGroupe();
        $client = ($clientFacturation) ? $clientFacturation : $client;
        $client_id = $client->getId();
        $numClient = 'C' . sprintf('%05d', $client_id);

        $interface = $mouvement->getInterface();
        $modePaiement = $mouvement->getModePaiement();

        $interface = strtoupper($mouvement->getInterface());
        $print_mouvement_lignes = $this->dataPrintMouvement($mouvement_id);
        //reglement
        $reglement = $em->getRepository(Reglement::class)->findOneBy(['mouvement' => $mouvement]);

        // ---------------------------
        $rows = $print_mouvement_lignes['rows'];
        $footers = $print_mouvement_lignes['footers'];
        $pages = [];
        $nl = $mouvement->getNombreLignePage();
        $nl = ($nl) ? $nl : 15;
        $i = 1;
        $p = 1;
        foreach ($rows as $print_mouvement_ligne) {
            $pages[$p][] = $print_mouvement_ligne;
            if ($i == $nl) {
                ++$p;
                $i = 1;
            } else {
                ++$i;
            }
        }
        $np = count($pages);
        // -------------------------
        $libelleTypeMouvement = $typeMouvement->getLibelle();
        $adresseClient = $myFct->findAdresseEntity($em, $client, 'e', true);
        //$adresseCentre = $myFct->findAdresseEntity($em, $centre, 'e', true);
        $basDePage = $mouvement->getCommentaire();
        $balises = ['<o:p>', '</o:p>'];
        $basDePage = str_replace($balises, '', $basDePage, $count);
        // on stocke la vue à convertir en PDF
        $myArray = [
            'mouvement' => $mouvement,
            'type' => $type,
            'pages' => $pages,
            'nl' => $nl,
            'np' => $np,
            'footers' => $footers,
            'typeMouvement' => $typeMouvement,
            'client' => $client,
            'numClient' => $numClient,
            'interface' => $interface,
            'filiale' => $filiale,
            'logo' => $logo,
            'modePaiement' => $modePaiement,
            'libelleTypeMouvement' => $libelleTypeMouvement,
            'adresseClient' => $adresseClient,
            //'adresseCentre' => $adresseCentre,
            'dateMouvement' => $dateMouvement,
            'basDePage' => $basDePage,
            'piedDePageMouvement' => $piedDePageMouvement,
            'reglement' => $reglement,
        ];
        $html = $this->renderView('mouvement/imprimer_mouvement.html.twig', $myArray);
        $footer = $this->renderView('mouvement/footer_mouvement.html.twig', $myArray);
        $pdfConfig2 = [
            'orientation' => 'Portrait',
            'page-size' => 'A4',
            'margin-top' => '15mm',
            'margin-right' => '5mm',
            'margin-bottom' => '30mm',
            'margin-left' => '5mm',
            'enable-local-file-access' => true,
            'disable-smart-shrinking' => true,
            'encoding' => 'UTF-8',
            'footer-html' => $footer,
        ];
        $numeroMouvement = $mouvement->getNumeroMouvement();
        return new Response(
            $knpSnappyPdf->getOutputFromHtml($html, $pdfConfig2),
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'filename="' . $numeroMouvement . '.pdf"',
            ]
        );
    }

    public function findAdresseEntity($em, $entity, $type, $principal = true)
    {
        $adresse_entity = null;
        $nomination = null;
        $adresseEntities = [
            'existe' => false,

            'nomination' => '',

            'codeCp' => '',

            'numeroRue' => '',

            'rue' => '',

            'complement' => '',

            'ville' => '',

            'contenu' => '',

            'adresse' => null,

            'codePostal' => null,

            'departement' => null,

            'departement_libelle' => '',

            'region' => null,

            'region_libelle' => '',

            'pays' => null,

            'pays_libelle' => '',
        ];

        if ($entity) {
            if ('p' == $type) {
                $nomination = $entity->getNomPrenoms();

                $adresse_entity = $entity->getAdresse(); // $em->getRepository(Adresse::class)->findOneBy(array("personne" => $entity, "principal" => $principal));
            } elseif ('e' == $type) {
                $nomination = $entity->getNomPublic();

                $adresse_entity = $entity->getAdresse(); // $em->getRepository(Adresse::class)->findOneBy(array("entreprise" => $entity, "principal" => $principal));
            }

            if ($adresse_entity) {
                $existe = true;

                $adresse = $adresse_entity; // ->getAdresse();

                $numeroRue = ($adresse) ? $adresse->getNumeroRue() : '';

                $rue = ($adresse) ? $adresse->getRue() : '';

                $complement = ($adresse) ? $adresse->getComplement() : '';

                $ville = ($adresse) ? $adresse->getVille() : '';

                $codePostal = ($adresse) ? $adresse->getCode() : null;

                $codeCp = $codePostal ?: '';

                $departement = ($adresse) ? $adresse->getDepartement() : null;

                $departement_libelle = $departement ?: '';

                $region = ($adresse) ? $adresse->getRegion() : null;

                $region_libelle = $region ?: '';

                $pays = ($adresse) ? $adresse->getPays() : null;

                $pays_libelle = $pays ?: '';

                $adresseEntities['existe'] = $existe;

                $adresseEntities['nomination'] = $nomination;

                $adresseEntities['codeCp'] = $codeCp;

                $adresseEntities['rue'] = $rue;

                $adresseEntities['numeroRue'] = $numeroRue;

                $adresseEntities['complement'] = $complement;

                $adresseEntities['ville'] = $ville;

                $adresseEntities['adresse'] = $adresse;

                $adresseEntities['codePostal'] = $codePostal;

                $adresseEntities['departement'] = $departement;

                $adresseEntities['departement_libelle'] = $departement_libelle;

                $adresseEntities['region'] = $region;

                $adresseEntities['region_libelle'] = $region_libelle;

                $adresseEntities['pays'] = $pays;

                $adresseEntities['pays_libelle'] = $pays_libelle;

                $adresseEntities['contenu'] = "$numeroRue $rue $codeCp $ville $complement $pays_libelle";
            }
        }

        return $adresseEntities;
    }
