<?php

declare(strict_types = 1);

return [
    'contact' => [
        'phone'    => '(81) 3333-0000',
        'whatsapp' => 'https://wa.me/',
        'emails'   => [
            'contato@tupan.com.br',
            'comercial@tupan.com.br',
        ],
        'page_emails' => [
            'comercial@tupan.com.br',
            'rh@tupan.com.br',
        ],
        'city'     => 'Recife, Pernambuco',
        'coverage' => 'Atuação em todo o Nordeste',
        'hours'    => 'Segunda a Sexta, 8h às 18h',
    ],
    'contact_forms' => [
        'default' => [
            'subject' => null,
            'topics'  => [
                'Consultoria Técnica em Produtos',
                'Engenharia Clínica (EquipaHosp)',
                'Cotação para Licitação',
                'Parceria Comercial',
                'Outros Assuntos',
            ],
        ],
        'equipa-hosp' => [
            'subject' => 'EquipaHosp — Engenharia Clínica e Equipamentos',
            'topics'  => [
                'Solução integrada para saúde',
                'Engenharia clínica',
                'Venda de equipamento',
                'Locação de equipamento',
                'Manutenção',
                'Calibração',
                'Assistência técnica',
                'Consultoria ou padronização',
                'Parceria comercial',
            ],
        ],
        'tupan-care' => [
            'subject' => 'Tupan Care — Curativos, Estomias e Padronização',
            'topics'  => [
                'Compra de produtos',
                'Padronização institucional',
                'Treinamento',
                'Curativos',
                'Estomias',
                'Parceria comercial',
                'Distribuição',
                'Outro',
            ],
        ],
    ],
    'divisions' => [
        [
            'id'               => 'cirurgica',
            'title'            => 'Loja Cirúrgica',
            'subtitle'         => 'Conhecimento técnico para procedimentos seguros',
            'description'      => 'Materiais cirúrgicos com respaldo técnico e consultoria especializada.',
            'full_description' => 'Nossa divisão cirúrgica vai além do fornecimento de materiais. Entendemos que cada instrumento, cada descartável, pode impactar diretamente o resultado de um procedimento e a segurança do paciente. Atuamos com um portfólio tecnicamente qualificado, desde instrumentais de precisão até descartáveis de alto giro, sempre com pronta entrega para não comprometer o fluxo hospitalar. Não vendemos apenas produtos. Intermediamos segurança.',
            'icon'             => 'stethoscope',
            'features'         => [
                'Instrumental Cirúrgico Qualificado',
                'Descartáveis Hospitalares',
                'Equipamentos de Centro Cirúrgico',
                'Material de Proteção Certificado',
            ],
            'target_audience' => [
                'Hospitais',
                'Clínicas Cirúrgicas',
                'Coordenadores de Enfermagem',
                'Médicos Cirurgiões',
            ],
            'image'           => 'images/institucional/division-cirurgica.png',
            'differentiators' => [
                'Pronta entrega garantida',
                'Consultoria técnica especializada',
                'Cobertura em todo o Nordeste',
                'Conformidade ANVISA',
            ],
        ],
        [
            'id'               => 'farma',
            'title'            => 'Medicamentos & Nutrição',
            'subtitle'         => 'Logística controlada, responsabilidade garantida',
            'description'      => 'Distribuição de fármacos e nutrição clínica com rastreabilidade total.',
            'full_description' => 'Através da Tupan Farma, atuamos como elo vital entre a indústria farmacêutica e as instituições de saúde. Entendemos que medicamentos e produtos nutricionais exigem cuidado absoluto em toda a cadeia. Nossa logística controlada garante a integridade de cada produto, desde a demanda hospitalar básica até necessidades específicas de nutrição clínica. Com 16 anos de experiência, construímos processos que honram a responsabilidade de lidar com o que sustenta vidas.',
            'icon'             => 'pill',
            'features'         => [
                'Medicamentos Hospitalares',
                'Nutrição Clínica Especializada',
                'Insumos Farmacêuticos',
                'Logística com Cadeia Fria',
            ],
            'target_audience' => [
                'Farmácias Hospitalares',
                'Farmacêuticos',
                'Nutricionistas Clínicos',
                'Gestores de Suprimentos',
            ],
            'image' => 'images/institucional/division-farma.png',
        ],
        [
            'id'               => 'curativos',
            'title'            => 'Curativos Especiais',
            'subtitle'         => 'Tecnologia avançada com suporte técnico consultivo',
            'description'      => 'Soluções de cicatrização com acompanhamento especializado.',
            'full_description' => 'A divisão AiTE é dedicada ao cuidado avançado de feridas. Não oferecemos apenas coberturas: oferecemos tecnologia, conhecimento e suporte consultivo. Trabalhamos com enfermeiras especialistas que entendem a prática assistencial e podem orientar sobre o produto mais adequado para cada caso. Nossas soluções aceleram a cicatrização e devolvem qualidade de vida ao paciente. Porque reduzir o tempo de tratamento é, em última instância, devolver a pessoa à sua rotina.',
            'icon'             => 'bandage',
            'features'         => [
                'Coberturas de Última Geração',
                'Tratamento de Feridas Crônicas',
                'Tecnologia de Cicatrização Avançada',
                'Insumos para Estomaterapia',
            ],
            'target_audience' => [
                'Enfermeiros Estomaterapeutas',
                'Hospitais',
                'Lojas Cirúrgicas',
                'Home Care',
            ],
            'image'           => 'images/institucional/division-curativos.png',
            'differentiators' => [
                'Equipe com enfermeira técnica',
                'Suporte consultivo na escolha do produto',
                'Treinamento para profissionais',
            ],
        ],
        [
            'id'               => 'lab',
            'title'            => 'Laboratório e Hemoterapia',
            'subtitle'         => 'Parceria com fabricantes de referência mundial',
            'description'      => 'Reagentes, insumos e equipamentos para diagnóstico preciso.',
            'full_description' => 'Fornecemos a base para diagnósticos precisos e hemoterapia segura. Atendemos laboratórios de análises clínicas e bancos de sangue em todo o Nordeste com exclusividade de fabricantes relevantes como Inbras e Fresenius. Entendemos a criticidade do que fazemos: um reagente ou bolsa de sangue não pode faltar, não pode falhar. Com 16 anos de experiência e parcerias sólidas com multinacionais, garantimos que o resultado final chegue com segurança ao paciente.',
            'icon'             => 'microscope',
            'features'         => [
                'Reagentes Químicos Certificados',
                'Insumos para Banco de Sangue',
                'Automação Laboratorial',
                'Consumíveis para Coleta',
            ],
            'target_audience' => [
                'Laboratórios de Análises Clínicas',
                'Bancos de Sangue',
                'Biomédicos',
                'Responsáveis Técnicos',
            ],
            'image'           => 'images/institucional/division-lab.png',
            'differentiators' => [
                'Exclusividade Inbras e Fresenius',
                'Parcerias com multinacionais',
                'Suporte técnico especializado',
            ],
        ],
        [
            'id'               => 'imagem',
            'title'            => 'Diagnóstico por Imagem',
            'subtitle'         => 'Atuação nacional com produto próprio',
            'description'      => 'Filmes, contrastes e acessórios para imagem diagnóstica.',
            'full_description' => 'Nossa divisão MT ultrapassa fronteiras regionais, atendendo empresas de diagnóstico por imagem em todo o Brasil. Trabalhamos com filmes, contrastes e acessórios que garantem a melhor resolução para laudos médicos assertivos. Desenvolvemos inclusive nosso primeiro produto de marca própria: seringas para inserção de contraste. É a prova de que não nos contentamos em fazer mais do mesmo, mas buscamos preencher lacunas de mercado com qualidade técnica superior.',
            'icon'             => 'scan',
            'features'         => [
                'Contrastes Radiológicos',
                'Filmes para Impressão',
                'Seringas para Contraste (Marca Própria)',
                'Acessórios para Exames de Imagem',
            ],
            'target_audience' => [
                'Clínicas de Diagnóstico por Imagem',
                'Hospitais',
                'Técnicos em Radiologia',
                'Gestores de Clínicas',
            ],
            'image'           => 'images/institucional/division-imagem.png',
            'differentiators' => [
                'Atuação Nacional',
                'Produto de marca própria',
                'Parcerias com grandes marcas',
                'Logística especializada',
            ],
        ],
        [
            'id'               => 'tupan-care',
            'title'            => 'Tupan Care',
            'subtitle'         => 'Soluções avançadas em cuidados de feridas, estomias e padronização assistencial.',
            'description'      => 'Soluções Gentell Casex, padronização, treinamento e apoio técnico para cuidados de feridas e estomias.',
            'menu_description' => 'Curativos, Estomias, Padronização e Educação em Saúde',
            'cta_label'        => 'Conhecer a Tupan Care',
            'full_description' => 'A Tupan Care é a operação da Tupan Saúde dedicada à promoção, representação e comercialização de soluções para cuidados de feridas e estomias.',
            'icon'             => 'heart',
            'features'         => [
                'Vendas locais Gentell Casex',
                'Padronização de produtos',
                'Treinamentos para equipes',
                'Curativos e estomias',
            ],
            'target_audience' => [
                'Hospitais',
                'Clínicas',
                'Serviços de home care',
                'Enfermeiros e estomaterapeutas',
            ],
            'image' => 'images/institucional/division-tupan-care.png',
            'view'  => 'livewire.institutional.divisions.tupan-care',
            'seo'   => [
                'title'       => 'Tupan Care | Soluções em Curativos e Estomias',
                'description' => 'A Tupan Care representa a Gentell Casex com vendas locais, padronização de produtos, treinamentos e apoio técnico para instituições e profissionais de saúde.',
            ],
            'contact_form' => 'tupan-care',
            'page'         => [
                'logo'        => null,
                'endorsement' => 'Uma operação Tupan Saúde',
                'intro'       => [
                    'A Tupan Care é a operação da Tupan Saúde dedicada à promoção, representação e comercialização de soluções para cuidados de feridas e estomias.',
                    'Como parceira local da Gentell Casex, atuamos junto a hospitais, clínicas, profissionais de saúde, distribuidores e instituições para apoiar a escolha, a padronização e a utilização adequada dos produtos.',
                    'Nossa atuação combina acesso a soluções especializadas, proximidade regional, treinamento e suporte técnico-comercial para contribuir com melhores decisões de cuidado e melhores resultados para os pacientes.',
                ],
                'partnership' => [
                    'title'      => 'Tupan Care + Gentell Casex',
                    'paragraphs' => [
                        'A parceria entre a Tupan Saúde e a Gentell Casex vem sendo construída desde 2023 e tem se fortalecido por meio de uma atuação conjunta no mercado de saúde.',
                        'A Gentell é uma empresa global especializada em soluções para cuidados de feridas. A Casex possui tradição na fabricação e no desenvolvimento de produtos para pessoas estomizadas. A Tupan Care aproxima esse portfólio das instituições e dos profissionais da nossa região, com atendimento, treinamento, promoção e apoio à padronização.',
                    ],
                    'highlight' => 'Mais do que disponibilizar produtos, a Tupan Care ajuda a transformar conhecimento técnico em decisões assistenciais mais seguras.',
                ],
                'approach' => [
                    [
                        'title'       => 'Vendas locais',
                        'description' => 'Atendimento comercial próximo para instituições, profissionais e parceiros que buscam soluções em curativos e estomias.',
                        'icon'        => 'map-pin',
                    ],
                    [
                        'title'       => 'Padronização de produtos',
                        'description' => 'Apoio às instituições na avaliação e na padronização de produtos conforme protocolos, perfis de pacientes, rotinas assistenciais e necessidades da operação.',
                        'icon'        => 'clipboard-document-check',
                    ],
                    [
                        'title'       => 'Treinamentos',
                        'description' => 'Ações de capacitação para equipes assistenciais e profissionais envolvidos na utilização dos produtos e nas rotinas de cuidado.',
                        'icon'        => 'academic-cap',
                    ],
                    [
                        'title'       => 'Divulgação técnica da marca',
                        'description' => 'Apresentação das soluções Gentell Casex ao mercado regional, com comunicação orientada por conhecimento e aplicação prática.',
                        'icon'        => 'megaphone',
                    ],
                    [
                        'title'       => 'Apoio a profissionais e instituições',
                        'description' => 'Proximidade para esclarecer dúvidas, identificar necessidades e encaminhar as melhores soluções para cada cenário.',
                        'icon'        => 'chat-bubble-left-right',
                    ],
                    [
                        'title'       => 'Desenvolvimento de mercado',
                        'description' => 'Construção de relacionamento com hospitais, clínicas, serviços de home care, profissionais de saúde, distribuidores e instituições públicas e privadas.',
                        'icon'        => 'arrow-trending-up',
                    ],
                ],
                'solutions' => [
                    [
                        'title'       => 'Cuidados de feridas',
                        'description' => 'Soluções para diferentes necessidades de prevenção, tratamento e acompanhamento de feridas, conforme indicação profissional e protocolos institucionais.',
                        'icon'        => 'heart',
                    ],
                    [
                        'title'       => 'Curativos avançados',
                        'description' => 'Produtos destinados a apoiar o manejo de feridas com diferentes níveis de complexidade.',
                        'icon'        => 'shield-check',
                    ],
                    [
                        'title'       => 'Produtos para estomizados',
                        'description' => 'Bolsas, acessórios e soluções de apoio para pessoas com estomia, profissionais e instituições de saúde.',
                        'icon'        => 'lifebuoy',
                    ],
                    [
                        'title'       => 'Educação e suporte',
                        'description' => 'Treinamentos, informações técnicas e apoio à implementação e à padronização das soluções.',
                        'icon'        => 'academic-cap',
                    ],
                ],
                'audience' => [
                    'Hospitais',
                    'Clínicas',
                    'Serviços de home care',
                    'Instituições de longa permanência',
                    'Coordenadores de enfermagem',
                    'Enfermeiros e estomaterapeutas',
                    'Comissão de curativos',
                    'Compras e suprimentos',
                    'Distribuidores',
                    'Profissionais que acompanham pacientes estomizados',
                    'Pacientes e familiares, sempre respeitando o limite de orientação institucional e profissional',
                ],
                'partners' => [
                    [
                        'name'        => 'Tupan Care',
                        'role'        => 'Operação local e atendimento',
                        'description' => 'Atendimento comercial, padronização, treinamento e apoio técnico próximos às instituições e aos profissionais da região.',
                        'url'         => null,
                        'logo'        => null,
                    ],
                    [
                        'name'        => 'Gentell Casex',
                        'role'        => 'Parceiro representado',
                        'description' => 'A Gentell Casex reúne soluções para cuidados de feridas e estomias, com produtos, conhecimento e suporte voltados à melhoria do cuidado e da qualidade de vida dos pacientes.',
                        'url'         => 'https://gentell.com.br/',
                        'logo'        => null,
                        'links'       => [
                            ['label' => 'Loja Gentell', 'url' => 'https://loja.gentell.com.br/'],
                            ['label' => 'Linha para estomizados', 'url' => 'https://casex.lojaintegrada.com.br/ostomia'],
                            ['label' => 'Linha de curativos', 'url' => 'https://gentell.com.br/curativo-hidrofibra-com-prata/'],
                        ],
                    ],
                    [
                        'name'        => 'Tupan Saúde',
                        'role'        => 'Grupo responsável pela operação',
                        'description' => 'A Tupan Care integra o ecossistema Tupan Saúde, com experiência técnica e presença regional.',
                        'url'         => 'https://tupansaude.com.br',
                        'logo'        => 'images/logo-tupan.png',
                    ],
                ],
                'cta' => [
                    'title'            => 'Sua instituição precisa revisar sua padronização de curativos ou soluções para estomias?',
                    'text'             => 'Fale com a Tupan Care e solicite uma conversa com nossa equipe.',
                    'primary_label'    => 'Solicitar contato',
                    'secondary_label'  => 'Solicitar treinamento',
                    'whatsapp_message' => 'Olá, gostaria de solicitar informações sobre treinamento, padronização ou soluções Gentell Casex pela Tupan Care.',
                ],
            ],
        ],
        [
            'id'               => 'equipahosp',
            'title'            => 'EquipaHosp',
            'subtitle'         => 'Um hub de soluções, tecnologias, produtos e serviços para tornar as operações de saúde mais eficientes e seguras.',
            'description'      => 'Engenharia clínica, equipamentos e suporte técnico para aumentar a segurança e a disponibilidade da operação de saúde.',
            'menu_description' => 'Engenharia Clínica, Equipamentos e Operações Técnicas',
            'cta_label'        => 'Conhecer a EquipaHosp',
            'full_description' => 'A EquipaHosp é o hub de soluções em saúde da Tupan Saúde. Reunimos parceiros, tecnologias, produtos, serviços e conhecimento técnico para apoiar hospitais, clínicas, laboratórios, centros de diagnóstico e demais instituições de saúde.',
            'icon'             => 'wrench',
            'features'         => [
                'Engenharia Clínica',
                'Venda e Locação de Equipamentos',
                'Manutenção, Calibração e Assistência Técnica',
                'Consultoria e Projetos em Saúde',
            ],
            'target_audience' => [
                'Hospitais',
                'Engenheiros Clínicos',
                'Administradores Hospitalares',
                'Coordenadores de Manutenção',
            ],
            'image' => 'images/institucional/division-equipahosp.png',
            'view'  => 'livewire.institutional.divisions.equipahosp',
            'seo'   => [
                'title'       => 'EquipaHosp | Engenharia Clínica e Equipamentos Hospitalares',
                'description' => 'Engenharia clínica, venda, locação, manutenção, calibração e assistência técnica para equipamentos hospitalares. Conheça a EquipaHosp, operação da Tupan Saúde.',
            ],
            'contact_form' => 'equipa-hosp',
            'page'         => [
                'eyebrow' => 'Hub de Soluções em Saúde',
                'intro'   => [
                    'A EquipaHosp é o hub de soluções em saúde da Tupan Saúde. Reunimos parceiros, tecnologias, produtos, serviços e conhecimento técnico para apoiar hospitais, clínicas, laboratórios, centros de diagnóstico e demais instituições de saúde.',
                    'Mais do que uma empresa de engenharia clínica ou assistência técnica, a EquipaHosp atua como uma plataforma de conexão entre as necessidades das instituições e soluções especializadas em equipamentos, tecnologia, gestão, suporte, locação, manutenção e operação em saúde.',
                    'Nosso papel é entender o desafio, reunir os recursos adequados e apoiar a instituição desde a avaliação da necessidade até a implantação, utilização, acompanhamento e evolução da solução.',
                ],
                'highlight' => 'Um hub para conectar pessoas, tecnologias e parceiros em favor de operações de saúde mais eficientes, seguras e sustentáveis.',
                'hub_items' => [
                    [
                        'title'       => 'Soluções em engenharia clínica',
                        'description' => 'Conexão com soluções para gestão técnica do parque de equipamentos, acompanhamento de ativos, manutenção, chamados, criticidade, contratos e indicadores.',
                        'icon'        => 'cog-6-tooth',
                    ],
                    [
                        'title'       => 'Venda e locação de equipamentos e tecnologias',
                        'description' => 'Representação e acesso a equipamentos e tecnologias adequados à necessidade, ao perfil assistencial e ao planejamento financeiro de cada instituição.',
                        'icon'        => 'building-storefront',
                    ],
                    [
                        'title'       => 'Serviços técnicos e assistência especializada',
                        'description' => 'Conexão com serviços de manutenção preventiva, corretiva, assistência técnica e suporte especializado para aumentar a disponibilidade e prolongar a vida útil dos equipamentos.',
                        'icon'        => 'wrench-screwdriver',
                    ],
                    [
                        'title'       => 'Calibração, conformidade e segurança operacional',
                        'description' => 'Apoio técnico e encaminhamento de soluções para manter equipamentos dentro dos requisitos de precisão, segurança e rastreabilidade aplicáveis.',
                        'icon'        => 'adjustments-horizontal',
                    ],
                    [
                        'title'       => 'Consultoria e projetos em saúde',
                        'description' => 'Apoio na avaliação de necessidades, especificação, padronização, aquisição, implantação e acompanhamento de soluções, com integração entre parceiros e áreas técnicas.',
                        'icon'        => 'clipboard-document-check',
                    ],
                    [
                        'title'       => 'Tecnologia e gestão aplicada',
                        'description' => 'Integração com soluções digitais para gestão de ativos, processos assistenciais, operação, indicadores e tomada de decisão.',
                        'icon'        => 'cpu-chip',
                    ],
                    [
                        'title'       => 'Parcerias e representação especializada',
                        'description' => 'Conexão com fabricantes, empresas de tecnologia, prestadores de serviços e especialistas para estruturar soluções adequadas a cada cenário.',
                        'icon'        => 'link',
                    ],
                ],
                'audience' => [
                    'Hospitais',
                    'Clínicas e centros médicos',
                    'Laboratórios de análises',
                    'Centros de diagnóstico por imagem',
                    'Bancos de sangue e unidades de hemoterapia',
                    'Unidades de terapia intensiva',
                    'Centros cirúrgicos',
                    'Clínicas de diagnóstico',
                    'Engenheiros clínicos',
                    'Coordenadores de manutenção',
                    'Administradores hospitalares',
                    'Gestores de compras e suprimentos',
                ],
                'partners' => [
                    [
                        'name'        => 'Blue Health',
                        'role'        => 'Locação e tecnologia médica',
                        'description' => 'Soluções de locação, venda e suporte técnico para equipamentos de diagnóstico por imagem, diagnóstico in vitro e suporte à vida, conectadas às necessidades das instituições atendidas pela EquipaHosp.',
                        'url'         => 'https://bluehealthglobal.com/',
                        'logo'        => null,
                    ],
                    [
                        'name'        => 'Fresenius Kabi',
                        'role'        => 'Soluções em saúde',
                        'description' => 'Soluções e tecnologias voltadas à saúde, incluindo medicamentos, nutrição e equipamentos para ambientes de cuidado crítico e crônico, conforme as linhas e condições comerciais aplicáveis à parceria.',
                        'url'         => 'https://www.fresenius-kabi.com/br',
                        'logo'        => null,
                    ],
                    [
                        'name'        => 'Cerne',
                        'role'        => 'Tecnologia e gestão',
                        'description' => 'Soluções próprias de tecnologia para gestão de ativos, processos assistenciais e operação em saúde, incluindo o Autivos Control e o Medical Dressing.',
                        'url'         => 'https://cerne.tec.br',
                        'logo'        => null,
                    ],
                    [
                        'name'        => 'Tupan Saúde',
                        'role'        => 'Grupo e operação regional',
                        'description' => 'A EquipaHosp integra o ecossistema Tupan Saúde, aproveitando a experiência técnica, a atuação regional e a rede de soluções do grupo.',
                        'url'         => 'https://tupansaude.com.br',
                        'logo'        => 'images/logo-tupan.png',
                    ],
                ],
                'reasons' => [
                    'Hub integrador de soluções para o setor de saúde',
                    'Acesso a parceiros, tecnologias, produtos e serviços especializados',
                    'Atendimento consultivo, não apenas comercial',
                    'Capacidade de conectar diferentes necessidades em uma solução coordenada',
                    'Apoio à padronização e à tomada de decisão',
                    'Atuação regional com visão de longo prazo',
                    'Integração entre equipamentos, tecnologia, processos e operação',
                    'Compromisso com segurança, eficiência e continuidade operacional',
                ],
                'cta' => [
                    'title'            => 'Sua instituição precisa encontrar uma solução para um desafio de saúde, tecnologia ou operação?',
                    'text'             => 'Fale com a equipe EquipaHosp e conte o que sua instituição precisa. Nosso hub conecta sua demanda aos parceiros e recursos mais adequados.',
                    'primary_label'    => 'Solicitar avaliação técnica',
                    'secondary_label'  => 'Falar com um consultor',
                    'whatsapp_message' => 'Olá, gostaria de falar com a EquipaHosp sobre uma necessidade de equipamentos ou engenharia clínica.',
                ],
            ],
        ],
    ],
    'blog_posts' => [
        [
            'id'        => 'engenharia-clinica-seguranca',
            'title'     => 'A Engenharia Clínica Como Pilar da Segurança do Paciente',
            'excerpt'   => 'Saúde não é lugar para improviso. Entenda como a gestão do parque tecnológico impacta diretamente nos resultados assistenciais e na segurança operacional.',
            'date'      => '12 Fev, 2024',
            'author'    => 'Equipe Técnica EquipaHosp',
            'category'  => 'Engenharia Clínica',
            'read_time' => '5 min de leitura',
            'image'     => 'https://images.unsplash.com/photo-1581093450021-4a7360e9a6b5?q=80&w=1200&auto=format&fit=crop',
            'content'   => <<<'HTML'
<p>A engenharia clínica desempenha papel fundamental na gestão de tecnologias de saúde. Em um ambiente hospitalar, a falha de um equipamento pode significar riscos graves à vida. Cada ventilador em uma UTI, cada monitor em um centro cirúrgico, precisa estar funcionando quando a vida depende dele.</p>

<h3>Manutenção Preventiva: Antecipar Problemas, Prolongar Vidas</h3>
<p>Muitas instituições ainda operam sob a lógica da manutenção corretiva, agindo apenas quando o equipamento apresenta falhas. No entanto, a abordagem preventiva visa antecipar problemas, prolongando a vida útil dos ativos e garantindo disponibilidade total. Não se trata apenas de economia, mas de responsabilidade.</p>

<h3>O Impacto Real na Assistência</h3>
<p>Equipamentos bem calibrados não apenas consomem menos recursos. Eles garantem que exames e procedimentos não sejam suspensos por inoperância técnica. E cada procedimento adiado pode significar um diagnóstico atrasado, um tratamento postergado, uma vida em risco.</p>

<p>Na TUPAN, através da EquipaHosp, entendemos que cuidar dos equipamentos é, em última instância, cuidar de vidas. Nossa equipe técnica atua em todo o Nordeste garantindo que a tecnologia trabalhe a favor da saúde.</p>
HTML,
        ],
        [
            'id'        => 'inovacao-tratamento-feridas',
            'title'     => 'Tecnologia em Curativos: Quando a Ciência Acelera a Cicatrização',
            'excerpt'   => 'Conheça como os avanços em coberturas especiais estão transformando o tratamento de feridas crônicas e devolvendo qualidade de vida aos pacientes.',
            'date'      => '05 Mar, 2024',
            'author'    => 'Equipe Técnica AiTE',
            'category'  => 'Curativos Especiais',
            'read_time' => '4 min de leitura',
            'image'     => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?q=80&w=1200&auto=format&fit=crop',
            'content'   => <<<'HTML'
<p>O tratamento de feridas crônicas e complexas tem passado por transformação significativa. O que antes se limitava a trocas simples de gazes, hoje envolve coberturas inteligentes capazes de interagir com o leito da lesão, controlando umidade e prevenindo infecções.</p>

<h3>O Papel das Coberturas Tecnológicas</h3>
<p>A divisão AiTE traz para o mercado soluções que incluem prata nanocristalina, alginatos e espumas de poliuretano de última geração. Esses materiais não apenas protegem, mas criam o microambiente ideal para a regeneração tecidual. E com suporte consultivo de enfermeiras especialistas, o profissional de saúde tem respaldo para escolher a solução mais adequada para cada caso.</p>

<h3>Devolvendo a Vida ao Paciente</h3>
<p>Reduzir o tempo de cicatrização significa devolver o paciente à sua rotina mais rápido. Menos dor nas trocas, menor frequência de procedimentos, recuperação da autonomia. Quando tratamos uma ferida com tecnologia adequada, tratamos também a dignidade de quem sofre com ela.</p>
HTML,
        ],
        [
            'id'        => 'autoridade-conhecimento-saude',
            'title'     => 'Autoridade Não Se Declara, Se Constrói',
            'excerpt'   => 'Como a busca por conhecimento técnico aplicado diferencia fornecedores de parceiros estratégicos no mercado de saúde.',
            'date'      => '20 Jan, 2024',
            'author'    => 'TUPAN Institucional',
            'category'  => 'Institucional',
            'read_time' => '3 min de leitura',
            'image'     => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=1200&auto=format&fit=crop',
            'content'   => <<<'HTML'
<p>Em um mercado onde sobra discurso e falta verdade, como identificar um parceiro realmente qualificado? A resposta está no histórico de entrega, no conhecimento demonstrado, na coerência entre o que se diz e o que se faz.</p>

<h3>Conhecimento que Se Aplica</h3>
<p>Na TUPAN, nosso fundador é farmacêutico. Nossa equipe técnica estuda, testa e valida antes de padronizar qualquer produto. Não fazemos mais do mesmo. Buscamos soluções com real qualificação técnica, de fabricantes que compartilham nosso compromisso com a qualidade.</p>

<h3>Parceiros, Não Apenas Fornecedores</h3>
<p>Queremos ser reconhecidos não como mais uma distribuidora, mas como consultores técnicos. Explicamos o porquê das escolhas, apoiamos decisões, construímos relacionamentos de longo prazo. Porque em saúde, a confiança se constrói com tempo, consistência e responsabilidade.</p>
HTML,
        ],
    ],
    'target_audience' => [
        [
            'id'          => 'b2b',
            'title'       => 'Instituições de Saúde',
            'description' => 'Parceria técnica para decisões mais seguras em ambientes críticos.',
            'profiles'    => [
                'Hospitais e Clínicas',
                'Laboratórios de Análises',
                'Bancos de Sangue',
                'Centros de Diagnóstico por Imagem',
                'Distribuidores Regionais',
            ],
            'icon'  => 'building-2',
            'color' => 'primary',
        ],
        [
            'id'          => 'b2p',
            'title'       => 'Profissionais de Saúde',
            'description' => 'Suporte consultivo para quem está na linha de frente do cuidado.',
            'profiles'    => [
                'Enfermeiros e Coordenadores de Enfermagem',
                'Biomédicos e Analistas Clínicos',
                'Engenheiros Clínicos',
                'Farmacêuticos Hospitalares',
                'Estomatoterapeutas',
            ],
            'icon'  => 'briefcase',
            'color' => 'secondary',
        ],
        [
            'id'          => 'b2c',
            'title'       => 'Home Care e Cuidado Domiciliar',
            'description' => 'Acesso a produtos profissionais com orientação técnica.',
            'profiles'    => [
                'Cuidadores Profissionais',
                'Familiares de Pacientes',
                'Pacientes em Tratamento Domiciliar',
            ],
            'icon'  => 'users',
            'color' => 'accent',
        ],
    ],
    'featured_highlights' => [
        [
            'id'          => 1,
            'title'       => 'Coberturas AiTE',
            'category'    => 'Tecnologia em Cicatrização',
            'description' => 'Curativos especiais com suporte consultivo de enfermeiras especialistas.',
            'image'       => 'https://images.unsplash.com/photo-1628595351029-c2bf17511435?q=80&w=600&auto=format&fit=crop',
        ],
        [
            'id'          => 2,
            'title'       => 'Reagentes Fresenius/Inbras',
            'category'    => 'Parceria com Multinacionais',
            'description' => 'Exclusividade de fabricantes de referência mundial para laboratórios e bancos de sangue.',
            'image'       => 'https://images.unsplash.com/photo-1579165466741-7f35a4755657?q=80&w=600&auto=format&fit=crop',
        ],
        [
            'id'          => 3,
            'title'       => 'Engenharia Clínica Completa',
            'category'    => 'Serviço EquipaHosp',
            'description' => 'Gestão do parque tecnológico: preventiva, corretiva, calibração e consultoria.',
            'image'       => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?q=80&w=600&auto=format&fit=crop',
        ],
        [
            'id'          => 4,
            'title'       => 'Seringas para Contraste',
            'category'    => 'Marca Própria MT',
            'description' => 'Nosso primeiro produto próprio: inovação onde o mercado precisava.',
            'image'       => 'https://images.unsplash.com/photo-1551076805-e1869033e561?q=80&w=600&auto=format&fit=crop',
        ],
    ],
    'testimonials' => [
        [
            'id'          => 1,
            'content'     => 'A TUPAN não é apenas fornecedor, é parceiro técnico. A equipe entende nossa realidade, antecipa necessidades e nos apoia nas decisões de padronização.',
            'author'      => 'Dr. Ricardo Mendes',
            'role'        => 'Diretor Clínico',
            'institution' => 'Hospital Santa Maria',
        ],
        [
            'id'          => 2,
            'content'     => 'Com a EquipaHosp, transformamos a gestão do nosso parque tecnológico. A manutenção preventiva reduziu custos e, mais importante, nos deu segurança operacional.',
            'author'      => 'Mariana Costa',
            'role'        => 'Engenheira Clínica',
            'institution' => 'Rede Diagnóstico PE',
        ],
        [
            'id'          => 3,
            'content'     => '16 anos de parceria. A TUPAN cresceu conosco, mas manteve a mesma seriedade do início. Isso é raro e valioso.',
            'author'      => 'Carlos Alberto',
            'role'        => 'Gestor de Compras',
            'institution' => 'Laboratório BioAnálise',
        ],
        [
            'id'          => 4,
            'content'     => 'A equipe de curativos AiTE conhece a prática assistencial. Não vendem produto, orientam a escolha. Faz toda a diferença no tratamento das feridas complexas.',
            'author'      => 'Enf. Patrícia Lima',
            'role'        => 'Enfermeira Estomaterapeuta',
            'institution' => 'Hospital Memorial',
        ],
    ],
];
