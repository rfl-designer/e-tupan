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
        'coverage' => 'Atuacao em todo o Nordeste',
        'hours'    => 'Segunda a Sexta, 8h as 18h',
    ],
    'divisions' => [
        [
            'id'               => 'cirurgica',
            'title'            => 'Loja Cirurgica',
            'subtitle'         => 'Conhecimento tecnico para procedimentos seguros',
            'description'      => 'Materiais cirurgicos com respaldo tecnico e consultoria especializada.',
            'full_description' => 'Nossa divisao cirurgica vai alem do fornecimento de materiais. Entendemos que cada instrumento, cada descartavel, pode impactar diretamente o resultado de um procedimento e a seguranca do paciente. Atuamos com um portfolio tecnicamente qualificado, desde instrumentais de precisao ate descartaveis de alto giro, sempre com pronta entrega para nao comprometer o fluxo hospitalar. Nao vendemos apenas produtos. Intermediamos seguranca.',
            'icon'             => 'stethoscope',
            'features'         => [
                'Instrumental Cirurgico Qualificado',
                'Descartaveis Hospitalares',
                'Equipamentos de Centro Cirurgico',
                'Material de Protecao Certificado',
            ],
            'target_audience' => [
                'Hospitais',
                'Clinicas Cirurgicas',
                'Coordenadores de Enfermagem',
                'Medicos Cirurgioes',
            ],
            'image'           => 'images/institucional/division-cirurgica.png',
            'differentiators' => [
                'Pronta entrega garantida',
                'Consultoria tecnica especializada',
                'Cobertura em todo o Nordeste',
                'Conformidade ANVISA',
            ],
        ],
        [
            'id'               => 'farma',
            'title'            => 'Medicamentos & Nutricao',
            'subtitle'         => 'Logistica controlada, responsabilidade garantida',
            'description'      => 'Distribuicao de farmacos e nutricao clinica com rastreabilidade total.',
            'full_description' => 'Atraves da Tupan Farma, atuamos como elo vital entre a industria farmaceutica e as instituicoes de saude. Entendemos que medicamentos e produtos nutricionais exigem cuidado absoluto em toda a cadeia. Nossa logistica controlada garante a integridade de cada produto, desde a demanda hospitalar basica ate necessidades especificas de nutricao clinica. Com 16 anos de experiencia, construimos processos que honram a responsabilidade de lidar com o que sustenta vidas.',
            'icon'             => 'pill',
            'features'         => [
                'Medicamentos Hospitalares',
                'Nutricao Clinica Especializada',
                'Insumos Farmaceuticos',
                'Logistica com Cadeia Fria',
            ],
            'target_audience' => [
                'Farmacias Hospitalares',
                'Farmaceuticos',
                'Nutricionistas Clinicos',
                'Gestores de Suprimentos',
            ],
            'image' => 'images/institucional/division-farma.png',
        ],
        [
            'id'               => 'curativos',
            'title'            => 'Curativos Especiais',
            'subtitle'         => 'Tecnologia avancada com suporte tecnico consultivo',
            'description'      => 'Solucoes de cicatrizacao com acompanhamento especializado.',
            'full_description' => 'A divisao AiTE e dedicada ao cuidado avancado de feridas. Nao oferecemos apenas coberturas: oferecemos tecnologia, conhecimento e suporte consultivo. Trabalhamos com enfermeiras especialistas que entendem a pratica assistencial e podem orientar sobre o produto mais adequado para cada caso. Nossas solucoes aceleram a cicatrizacao e devolvem qualidade de vida ao paciente. Porque reduzir o tempo de tratamento e, em ultima instancia, devolver a pessoa a sua rotina.',
            'icon'             => 'bandage',
            'features'         => [
                'Coberturas de Ultima Geracao',
                'Tratamento de Feridas Cronicas',
                'Tecnologia de Cicatrizacao Avancada',
                'Insumos para Estomaterapia',
            ],
            'target_audience' => [
                'Enfermeiros Estomatoterapeutas',
                'Hospitais',
                'Lojas Cirurgicas',
                'Home Care',
            ],
            'image'           => 'images/institucional/division-curativos.png',
            'differentiators' => [
                'Equipe com enfermeira tecnica',
                'Suporte consultivo na escolha do produto',
                'Treinamento para profissionais',
            ],
        ],
        [
            'id'               => 'lab',
            'title'            => 'Laboratorio e Hemoterapia',
            'subtitle'         => 'Parceria com fabricantes de referencia mundial',
            'description'      => 'Reagentes, insumos e equipamentos para diagnostico preciso.',
            'full_description' => 'Fornecemos a base para diagnosticos precisos e hemoterapia segura. Atendemos laboratorios de analises clinicas e bancos de sangue em todo o Nordeste com exclusividade de fabricantes relevantes como Inbras e Fresenius. Entendemos a criticidade do que fazemos: um reagente ou bolsa de sangue nao pode faltar, nao pode falhar. Com 16 anos de experiencia e parcerias solidas com multinacionais, garantimos que o resultado final chegue com seguranca ao paciente.',
            'icon'             => 'microscope',
            'features'         => [
                'Reagentes Quimicos Certificados',
                'Insumos para Banco de Sangue',
                'Automacao Laboratorial',
                'Consumiveis para Coleta',
            ],
            'target_audience' => [
                'Laboratorios de Analises Clinicas',
                'Bancos de Sangue',
                'Biomedicos',
                'Responsaveis Tecnicos',
            ],
            'image'           => 'images/institucional/division-lab.png',
            'differentiators' => [
                'Exclusividade Inbras e Fresenius',
                'Parcerias com multinacionais',
                'Suporte tecnico especializado',
            ],
        ],
        [
            'id'               => 'imagem',
            'title'            => 'Diagnostico por Imagem',
            'subtitle'         => 'Atuacao nacional com produto proprio',
            'description'      => 'Filmes, contrastes e acessorios para imagem diagnostica.',
            'full_description' => 'Nossa divisao MT ultrapassa fronteiras regionais, atendendo empresas de diagnostico por imagem em todo o Brasil. Trabalhamos com filmes, contrastes e acessorios que garantem a melhor resolucao para laudos medicos assertivos. Desenvolvemos inclusive nosso primeiro produto de marca propria: seringas para insercao de contraste. E a prova de que nao nos contentamos em fazer mais do mesmo, mas buscamos preencher lacunas de mercado com qualidade tecnica superior.',
            'icon'             => 'scan',
            'features'         => [
                'Contrastes Radiologicos',
                'Filmes para Impressao',
                'Seringas para Contraste (Marca Propria)',
                'Acessorios para Exames de Imagem',
            ],
            'target_audience' => [
                'Clinicas de Diagnostico por Imagem',
                'Hospitais',
                'Tecnicos em Radiologia',
                'Gestores de Clinicas',
            ],
            'image'           => 'images/institucional/division-imagem.png',
            'differentiators' => [
                'Atuacao Nacional',
                'Produto de marca propria',
                'Parcerias com grandes marcas',
                'Logistica especializada',
            ],
        ],
        [
            'id'               => 'proprios',
            'title'            => 'Marcas Proprias & Importacao',
            'subtitle'         => 'Inovacao onde o mercado precisa',
            'description'      => 'Produtos exclusivos com rigoroso controle tecnico.',
            'full_description' => 'Desenvolvemos e importamos produtos que levam a marca TUPAN e AiTE, focando em preencher lacunas de mercado com qualidade tecnica superior e custo-beneficio real. Nao fazemos mais do mesmo. Estudamos, validamos e so entao padronizamos. Nossa linha propria e sinonimo de seguranca e conformidade regulatoria, construida com a mesma seriedade que marca toda nossa trajetoria de 16 anos.',
            'icon'             => 'package-check',
            'features'         => [
                'Importacao Direta Controlada',
                'Certificacao ANVISA',
                'Linha AiTE',
                'Produtos Exclusivos Validados',
            ],
            'target_audience' => [
                'Distribuidores Regionais',
                'Grandes Redes Hospitalares',
                'Governo e Licitacoes',
            ],
            'image' => 'images/institucional/division-tupan-care.png',
        ],
        [
            'id'               => 'equipahosp',
            'title'            => 'EquipaHosp',
            'subtitle'         => 'Engenharia Clinica e Assistencia Tecnica Especializada',
            'description'      => 'Gestao do parque tecnologico hospitalar com suporte completo.',
            'full_description' => 'A EquipaHosp representa nossa visao de que saude nao e lugar para improviso. Cada equipamento em uma UTI, cada ventilador em um centro cirurgico, precisa estar funcionando quando a vida depende dele. Oferecemos engenharia clinica completa: venda de equipamentos, locacao, manutencao preventiva e corretiva, calibracao e consultoria. Queremos ser parceiros de multinacionais e executar projetos com a eficiencia que o setor exige. Porque cuidar dos equipamentos e, em ultima instancia, cuidar de vidas.',
            'icon'             => 'wrench',
            'features'         => [
                'Engenharia Clinica Completa',
                'Manutencao Preventiva e Corretiva',
                'Calibracao Certificada',
                'Venda e Locacao de Equipamentos',
            ],
            'target_audience' => [
                'Engenheiros Clinicos',
                'Administradores Hospitalares',
                'Gestores de UTI e Centro Cirurgico',
                'Coordenadores de Manutencao',
            ],
            'image'           => 'images/institucional/division-equipahosp.png',
            'differentiators' => [
                'Suporte tecnico 24h',
                'Equipe especializada',
                'Atuacao em todo o Nordeste',
                'Parceria com multinacionais',
            ],
        ],
    ],
    'blog_posts' => [
        [
            'id'        => 'engenharia-clinica-seguranca',
            'title'     => 'A Engenharia Clinica Como Pilar da Seguranca do Paciente',
            'excerpt'   => 'Saude nao e lugar para improviso. Entenda como a gestao do parque tecnologico impacta diretamente nos resultados assistenciais e na seguranca operacional.',
            'date'      => '12 Fev, 2024',
            'author'    => 'Equipe Tecnica EquipaHosp',
            'category'  => 'Engenharia Clinica',
            'read_time' => '5 min de leitura',
            'image'     => 'https://images.unsplash.com/photo-1581093450021-4a7360e9a6b5?q=80&w=1200&auto=format&fit=crop',
            'content'   => <<<'HTML'
<p>A engenharia clinica desempenha papel fundamental na gestao de tecnologias de saude. Em um ambiente hospitalar, a falha de um equipamento pode significar riscos graves a vida. Cada ventilador em uma UTI, cada monitor em um centro cirurgico, precisa estar funcionando quando a vida depende dele.</p>

<h3>Manutencao Preventiva: Antecipar Problemas, Prolongar Vidas</h3>
<p>Muitas instituicoes ainda operam sob a logica da manutencao corretiva, agindo apenas quando o equipamento apresenta falhas. No entanto, a abordagem preventiva visa antecipar problemas, prolongando a vida util dos ativos e garantindo disponibilidade total. Nao se trata apenas de economia, mas de responsabilidade.</p>

<h3>O Impacto Real na Assistencia</h3>
<p>Equipamentos bem calibrados nao apenas consomem menos recursos. Eles garantem que exames e procedimentos nao sejam suspensos por inoperancia tecnica. E cada procedimento adiado pode significar um diagnostico atrasado, um tratamento postergado, uma vida em risco.</p>

<p>Na TUPAN, atraves da EquipaHosp, entendemos que cuidar dos equipamentos e, em ultima instancia, cuidar de vidas. Nossa equipe tecnica atua em todo o Nordeste garantindo que a tecnologia trabalhe a favor da saude.</p>
HTML,
        ],
        [
            'id'        => 'inovacao-tratamento-feridas',
            'title'     => 'Tecnologia em Curativos: Quando a Ciencia Acelera a Cicatrizacao',
            'excerpt'   => 'Conheca como os avancos em coberturas especiais estao transformando o tratamento de feridas cronicas e devolvendo qualidade de vida aos pacientes.',
            'date'      => '05 Mar, 2024',
            'author'    => 'Equipe Tecnica AiTE',
            'category'  => 'Curativos Especiais',
            'read_time' => '4 min de leitura',
            'image'     => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?q=80&w=1200&auto=format&fit=crop',
            'content'   => <<<'HTML'
<p>O tratamento de feridas cronicas e complexas tem passado por transformacao significativa. O que antes se limitava a trocas simples de gazes, hoje envolve coberturas inteligentes capazes de interagir com o leito da lesao, controlando umidade e prevenindo infeccoes.</p>

<h3>O Papel das Coberturas Tecnologicas</h3>
<p>A divisao AiTE traz para o mercado solucoes que incluem prata nanocristalina, alginatos e espumas de poliuretano de ultima geracao. Esses materiais nao apenas protegem, mas criam o microambiente ideal para a regeneracao tecidual. E com suporte consultivo de enfermeiras especialistas, o profissional de saude tem respaldo para escolher a solucao mais adequada para cada caso.</p>

<h3>Devolvendo a Vida ao Paciente</h3>
<p>Reduzir o tempo de cicatrizacao significa devolver o paciente a sua rotina mais rapido. Menos dor nas trocas, menor frequencia de procedimentos, recuperacao da autonomia. Quando tratamos uma ferida com tecnologia adequada, tratamos tambem a dignidade de quem sofre com ela.</p>
HTML,
        ],
        [
            'id'        => 'autoridade-conhecimento-saude',
            'title'     => 'Autoridade Nao Se Declara, Se Constroi',
            'excerpt'   => 'Como a busca por conhecimento tecnico aplicado diferencia fornecedores de parceiros estrategicos no mercado de saude.',
            'date'      => '20 Jan, 2024',
            'author'    => 'TUPAN Institucional',
            'category'  => 'Institucional',
            'read_time' => '3 min de leitura',
            'image'     => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=1200&auto=format&fit=crop',
            'content'   => <<<'HTML'
<p>Em um mercado onde sobra discurso e falta verdade, como identificar um parceiro realmente qualificado? A resposta esta no historico de entrega, no conhecimento demonstrado, na coerencia entre o que se diz e o que se faz.</p>

<h3>Conhecimento que Se Aplica</h3>
<p>Na TUPAN, nosso fundador e farmaceutico. Nossa equipe tecnica estuda, testa e valida antes de padronizar qualquer produto. Nao fazemos mais do mesmo. Buscamos solucoes com real qualificacao tecnica, de fabricantes que compartilham nosso compromisso com a qualidade.</p>

<h3>Parceiros, Nao Apenas Fornecedores</h3>
<p>Queremos ser reconhecidos nao como mais uma distribuidora, mas como consultores tecnicos. Explicamos o porque das escolhas, apoiamos decisoes, construimos relacionamentos de longo prazo. Porque em saude, a confianca se constroi com tempo, consistencia e responsabilidade.</p>
HTML,
        ],
    ],
    'target_audience' => [
        [
            'id'          => 'b2b',
            'title'       => 'Instituicoes de Saude',
            'description' => 'Parceria tecnica para decisoes mais seguras em ambientes criticos.',
            'profiles'    => [
                'Hospitais e Clinicas',
                'Laboratorios de Analises',
                'Bancos de Sangue',
                'Centros de Diagnostico por Imagem',
                'Distribuidores Regionais',
            ],
            'icon'  => 'building-2',
            'color' => 'primary',
        ],
        [
            'id'          => 'b2p',
            'title'       => 'Profissionais de Saude',
            'description' => 'Suporte consultivo para quem esta na linha de frente do cuidado.',
            'profiles'    => [
                'Enfermeiros e Coordenadores de Enfermagem',
                'Biomedicos e Analistas Clinicos',
                'Engenheiros Clinicos',
                'Farmaceuticos Hospitalares',
                'Estomatoterapeutas',
            ],
            'icon'  => 'briefcase',
            'color' => 'secondary',
        ],
        [
            'id'          => 'b2c',
            'title'       => 'Home Care e Cuidado Domiciliar',
            'description' => 'Acesso a produtos profissionais com orientacao tecnica.',
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
            'category'    => 'Tecnologia em Cicatrizacao',
            'description' => 'Curativos especiais com suporte consultivo de enfermeiras especialistas.',
            'image'       => 'https://images.unsplash.com/photo-1628595351029-c2bf17511435?q=80&w=600&auto=format&fit=crop',
        ],
        [
            'id'          => 2,
            'title'       => 'Reagentes Fresenius/Inbras',
            'category'    => 'Parceria com Multinacionais',
            'description' => 'Exclusividade de fabricantes de referencia mundial para laboratorios e bancos de sangue.',
            'image'       => 'https://images.unsplash.com/photo-1579165466741-7f35a4755657?q=80&w=600&auto=format&fit=crop',
        ],
        [
            'id'          => 3,
            'title'       => 'Engenharia Clinica Completa',
            'category'    => 'Servico EquipaHosp',
            'description' => 'Gestao do parque tecnologico: preventiva, corretiva, calibracao e consultoria.',
            'image'       => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?q=80&w=600&auto=format&fit=crop',
        ],
        [
            'id'          => 4,
            'title'       => 'Seringas para Contraste',
            'category'    => 'Marca Propria MT',
            'description' => 'Nosso primeiro produto proprio: inovacao onde o mercado precisava.',
            'image'       => 'https://images.unsplash.com/photo-1551076805-e1869033e561?q=80&w=600&auto=format&fit=crop',
        ],
    ],
    'testimonials' => [
        [
            'id'          => 1,
            'content'     => 'A TUPAN nao e apenas fornecedor, e parceiro tecnico. A equipe entende nossa realidade, antecipa necessidades e nos apoia nas decisoes de padronizacao.',
            'author'      => 'Dr. Ricardo Mendes',
            'role'        => 'Diretor Clinico',
            'institution' => 'Hospital Santa Maria',
        ],
        [
            'id'          => 2,
            'content'     => 'Com a EquipaHosp, transformamos a gestao do nosso parque tecnologico. A manutencao preventiva reduziu custos e, mais importante, nos deu seguranca operacional.',
            'author'      => 'Mariana Costa',
            'role'        => 'Engenheira Clinica',
            'institution' => 'Rede Diagnostico PE',
        ],
        [
            'id'          => 3,
            'content'     => '16 anos de parceria. A TUPAN cresceu conosco, mas manteve a mesma seriedade do inicio. Isso e raro e valioso.',
            'author'      => 'Carlos Alberto',
            'role'        => 'Gestor de Compras',
            'institution' => 'Laboratorio BioAnalise',
        ],
        [
            'id'          => 4,
            'content'     => 'A equipe de curativos AiTE conhece a pratica assistencial. Nao vendem produto, orientam escolha. Faz toda a diferenca no tratamento das feridas complexas.',
            'author'      => 'Enf. Patricia Lima',
            'role'        => 'Enfermeira Estomaterapeuta',
            'institution' => 'Hospital Memorial',
        ],
    ],
];
