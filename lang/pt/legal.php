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
];
