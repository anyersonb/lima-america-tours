<?php

return [
    'terms_title' => 'Termos e Condições',
    'privacy_title' => 'Política de Privacidade',
    'last_updated' => 'Última atualização: 7 de maio de 2026',

    // Terms sections
    'terms_s1_title' => 'Aceitação dos Termos',
    'terms_s1_body' => 'Ao acessar e utilizar os serviços da Lima América Tours, você concorda em estar vinculado por estes termos e condições. Se não concordar com alguma parte destes termos, pedimos que não utilize nossos serviços. A Lima América Tours reserva-se o direito de atualizar estes termos a qualquer momento, notificando os usuários mediante a publicação da nova versão neste site.',

    'terms_s2_title' => 'Descrição do Serviço',
    'terms_s2_body' => 'A Lima América Tours é uma empresa dedicada à organização e comercialização de tours e experiências turísticas em Lima, Ica, Cusco e outras regiões do Peru. Nossos serviços incluem planejamento de roteiros, transporte, guias turísticos certificados e atividades culturais. Os preços, disponibilidade e roteiros podem ser alterados sem aviso prévio por motivos operacionais ou de força maior.',

    'terms_s3_title' => 'Reservas e Pagamentos',
    'terms_s3_body' => 'Ao concluir sua reserva, você pode optar por pagar imediatamente via PayPal (cartão de crédito/débito ou saldo PayPal) ou reservar agora e combinar o pagamento depois. Em ambos os casos, a reserva é confirmada por e-mail e, se necessário, por WhatsApp, onde nossa equipe verifica os detalhes do tour e combina a forma de pagamento. A Lima América Tours não armazena dados de cartões: quando o pagamento é imediato, o processamento é gerenciado integralmente pelo PayPal, um provedor de pagamento certificado.',

    'terms_s4_title' => 'Cancelamentos e Reembolsos',
    'terms_s4_body' => 'Se sua reserva estiver pendente de pagamento ("reserve agora, pague depois"), você pode cancelá-la sem custo a qualquer momento antes da data do tour, já que nenhuma cobrança foi feita. Para reservas já pagas via PayPal: os cancelamentos realizados com mais de 48 horas de antecedência à data do tour receberão reembolso de 80% do valor pago; os efetuados entre 24 e 48 horas antes terão direito a reembolso de 50%. Não haverá reembolso por cancelamentos com menos de 24 horas de antecedência nem por ausências no ponto de partida. Em casos de força maior (desastres naturais, greves, restrições governamentais), será oferecida remarcação sem custo adicional.',

    'terms_s5_title' => 'Contato',
    // Reestruturado em 2026-08-11: o parágrafo único trazia um telefone
    // (+51 935 542 384, de NENHUM cliente desta agência), um horário e um
    // endereço fixos, publicados em três idiomas — ver
    // App\Models\Setting::contactPhone()/contactHours()/contactAddress(),
    // fonte única agora. Cada linha só aparece se o painel tiver o dado.
    'terms_s5_intro' => 'Para qualquer dúvida relacionada a estes termos, você pode entrar em contato conosco pelo formulário em nossa página de contato ou por e-mail.',
    'terms_s5_phone_line' => 'Você também pode ligar para :phone.',
    'terms_s5_hours_line' => 'Nossa equipe atende :hours.',
    'terms_s5_address_line' => 'Lima América Tours — :address.',

    // Privacy sections
    'privacy_s1_title' => 'Dados que Coletamos',
    'privacy_s1_body' => 'A Lima América Tours coleta apenas os dados estritamente necessários para prestar nossos serviços. Isso inclui: nome completo, e-mail e número de telefone ao realizar uma reserva ou assinar a newsletter; dados de navegação anônimos (cookies de análise) para melhorar a experiência do usuário; e, somente quando você opta por pagar imediatamente, informações de pagamento que são processadas diretamente pelo PayPal e não são armazenadas em nossos servidores.',

    'privacy_s2_title' => 'Uso dos Dados',
    'privacy_s2_body' => 'Utilizamos suas informações pessoais exclusivamente para: confirmar e gerenciar reservas, enviar comunicações relacionadas ao seu tour, melhorar nossos serviços por meio de análise agregada e, com seu consentimento expresso, enviar newsletters com ofertas e novidades. Não utilizaremos seus dados para tomada de decisões automatizadas nem para criação de perfis sem o seu consentimento.',

    'privacy_s3_title' => 'Compartilhamento de Informações',
    'privacy_s3_body' => 'A Lima América Tours não vende, aluga nem comercializa suas informações pessoais a terceiros. Compartilhamos dados apenas com fornecedores operacionais indispensáveis (a plataforma de pagamento PayPal quando você opta por pagar imediatamente, serviço de envio de e-mails transacionais) que se comprometem contratualmente a tratar os dados com o mesmo nível de proteção. Podemos divulgar informações quando exigido por lei ou para proteger nossos direitos legais.',

    'privacy_s4_title' => 'Cookies',
    'privacy_s4_body' => 'Utilizamos cookies próprios para o funcionamento do carrinho de compras e o gerenciamento de sessões, bem como cookies de terceiros do Google Analytics (anônimos) para análise de tráfego. Você pode configurar seu navegador para recusar cookies; observe que isso pode afetar a funcionalidade do site. Ao continuar navegando, você aceita nossa política de cookies.',

    'privacy_s5_title' => 'Direitos do Usuário',
    'privacy_s5_body' => 'Em conformidade com a Lei N.° 29733 de Proteção de Dados Pessoais do Peru, você tem o direito de acessar, retificar, cancelar ou se opor ao tratamento de seus dados pessoais.',
    // A menção a "Jr. Lampa 209, Lima Center" foi removida em 2026-08-11: não
    // é um endereço confirmado (ver App\Models\Setting::contactAddress()).
    'privacy_s5_exercise_email' => 'Para exercer esses direitos, envie uma solicitação por escrito para nosso e-mail.',
    'privacy_s5_exercise_email_and_address' => 'Para exercer esses direitos, envie uma solicitação por escrito para nosso e-mail ou endereço físico (:address).',
    'privacy_s5_response_time' => 'Responderemos em um prazo máximo de 20 dias úteis.',

    // ── ESNNA — código de conduta (ver a nota em lang/es/legal.php) ────────
    'esnna_last_updated' => 'Última atualização: 24 de agosto de 2026',
    'esnna_title' => 'Código de conduta contra a ESNNA',
    'esnna_intro' => 'A Lima América Tours rejeita de forma absoluta a exploração sexual de crianças e adolescentes (ESNNA) no âmbito do turismo e assume o compromisso público de preveni-la e denunciá-la.',

    'esnna_poster_alt' => 'Cartaz oficial do MINCETUR: nesta agência não promovemos nem permitimos a exploração sexual de crianças e adolescentes, conforme a Lei n.º 29408 do Peru. Denuncie pela linha gratuita 1818 ou pela Línea 100.',
    'esnna_poster_caption' => 'Cartaz oficial do Ministério do Comércio Exterior e Turismo do Peru. Clique para ver em tamanho real.',

    'esnna_s1_title' => 'Nosso compromisso',
    'esnna_s1_body' => 'Como agência de viagens peruana, partimos do princípio de que o turismo não pode ser um caminho para o abuso. Comprometemo-nos a não facilitar, promover, tolerar nem acobertar qualquer forma de exploração sexual de crianças e adolescentes, nem por ação própria nem por meio de terceiros que trabalhem conosco. Este compromisso vale para toda a nossa equipe, nossos guias e cada fornecedor de transporte, hospedagem e atividades com quem operamos.',

    'esnna_s2_title' => 'O que é a ESNNA',
    'esnna_s2_body' => 'A exploração sexual de crianças e adolescentes é qualquer situação em que uma pessoa com menos de 18 anos é utilizada para atividades sexuais em troca de dinheiro, bens, favores ou qualquer outra vantagem, para quem a explora ou para um terceiro. Não é trabalho, não é uma escolha e não deixa de ser crime porque houve um pagamento, um intermediário ou o consentimento aparente da vítima ou de sua família.',

    'esnna_s3_title' => 'O que fazemos na prática',
    'esnna_s3_body' => 'Informamos e capacitamos nossa equipe para reconhecer sinais de risco; incluímos este compromisso nos acordos com nossos fornecedores; recusamos qualquer solicitação de serviços que possa ter como fim a exploração de menores, cancelando a reserva sem reembolso; e comunicamos às autoridades competentes qualquer fato ou indício que detectemos, protegendo a identidade da vítima e de quem informa.',

    'esnna_s4_title' => 'Como denunciar',
    'esnna_s4_intro' => 'Se você conhece ou suspeita de um caso, a denúncia é gratuita, pode ser anônima e não precisa de provas: basta a suspeita razoável.',
    'esnna_s4_line100' => 'Línea 100 (Ministério da Mulher e Populações Vulneráveis): disque 100, gratuito, 24 horas por dia, de qualquer telefone no Peru.',
    'esnna_s4_police' => 'Polícia Nacional do Peru: disque 105 em caso de emergência ou vá à delegacia mais próxima.',
    'esnna_s4_us' => 'Você também pode nos escrever: se o que você viu aconteceu durante um de nossos tours ou envolveu um de nossos fornecedores, queremos saber para agir e denunciar.',

    'esnna_s5_title' => 'Marco legal',
    'esnna_s5_body' => 'No Peru, a exploração sexual comercial de crianças e adolescentes no âmbito do turismo é crime previsto no Código Penal a partir da Lei N.° 28251. A Lei N.° 29408, Lei Geral de Turismo, obriga os prestadores de serviços turísticos a colaborar na sua prevenção. Este código de conduta é a nossa aplicação dessas obrigações e é revisado sempre que a normativa muda.',
];
