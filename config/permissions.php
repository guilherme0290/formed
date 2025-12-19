<?php

return [
    // MASTER (apenas módulo master)
    'master' => [
        'master.dashboard.view'      => 'Acessar painel master',
        'master.acessos.manage'      => 'Gerir acessos/usuários',
        'master.papeis.manage'       => 'Gerir papéis',
        'master.permissoes.manage'   => 'Gerir permissões',
        'master.usuarios.manage'     => 'Gerir usuários',

        'master.tabela-precos.view'  => 'Ver tabela de preços',
        'master.tabela-precos.create'=> 'Criar itens tabela de preços',
        'master.tabela-precos.update'=> 'Editar itens tabela de preços',
        'master.tabela-precos.delete'=> 'Remover itens tabela de preços',

        'master.comissoes.view'      => 'Ver parametrização de comissões',
        'master.comissoes.update'    => 'Editar parametrização de comissões',

        'master.clientes.view'       => 'Ver clientes',
        'master.clientes.create'     => 'Criar clientes',
        'master.clientes.update'     => 'Editar clientes',
        'master.clientes.delete'     => 'Excluir clientes',
    ],

    // COMERCIAL (apenas módulo comercial)
    'comercial' => [
        'comercial.dashboard.view'        => 'Acessar painel comercial',

        'comercial.propostas.view'        => 'Ver propostas',
        'comercial.propostas.create'      => 'Criar propostas',
        'comercial.propostas.update'      => 'Editar propostas',
        'comercial.propostas.delete'      => 'Excluir propostas',

        'comercial.pipeline.view'         => 'Ver pipeline/acompanhamento',

        'comercial.tabela-precos.view'    => 'Ver tabela de preços',
        'comercial.tabela-precos.create'  => 'Criar itens tabela de preços',
        'comercial.tabela-precos.update'  => 'Editar itens tabela de preços',
        'comercial.tabela-precos.delete'  => 'Excluir itens tabela de preços',

        'comercial.contratos.view'        => 'Ver contratos',
        'comercial.contratos.create'      => 'Criar contratos',
        'comercial.contratos.update'      => 'Editar contratos',
        'comercial.contratos.delete'      => 'Excluir contratos',

        'comercial.comissoes.view'        => 'Ver minhas comissões',

        'comercial.agenda.view'           => 'Ver agenda',
        'comercial.agenda.edit'           => 'Criar/editar tarefas agenda',
    ],

    // OPERACIONAL (apenas módulo operacional)
    'operacional' => [
        'operacional.dashboard.view'      => 'Ver kanban/operacional',

        'operacional.aso.view'            => 'Ver ASO',
        'operacional.aso.create'          => 'Criar ASO',
        'operacional.aso.update'          => 'Editar ASO',
        'operacional.aso.delete'          => 'Excluir ASO',

        'operacional.pgr.view'            => 'Ver PGR',
        'operacional.pgr.create'          => 'Criar PGR',
        'operacional.pgr.update'          => 'Editar PGR',
        'operacional.pgr.delete'          => 'Excluir PGR',

        'operacional.pcmso.view'          => 'Ver PCMSO',
        'operacional.pcmso.create'        => 'Criar PCMSO',
        'operacional.pcmso.update'        => 'Editar PCMSO',
        'operacional.pcmso.delete'        => 'Excluir PCMSO',

        'operacional.ltcat.view'          => 'Ver LTCAT',
        'operacional.ltcat.create'        => 'Criar LTCAT',
        'operacional.ltcat.update'        => 'Editar LTCAT',
        'operacional.ltcat.delete'        => 'Excluir LTCAT',

        'operacional.ltip.view'           => 'Ver LTIP',
        'operacional.ltip.create'         => 'Criar LTIP',
        'operacional.ltip.update'         => 'Editar LTIP',
        'operacional.ltip.delete'         => 'Excluir LTIP',

        'operacional.apr.view'            => 'Ver APR',
        'operacional.apr.create'          => 'Criar APR',
        'operacional.apr.update'          => 'Editar APR',
        'operacional.apr.delete'          => 'Excluir APR',

        'operacional.pae.view'            => 'Ver PAE',
        'operacional.pae.create'          => 'Criar PAE',
        'operacional.pae.update'          => 'Editar PAE',
        'operacional.pae.delete'          => 'Excluir PAE',

        'operacional.treinamentos.view'   => 'Ver Treinamentos NR',
        'operacional.treinamentos.create' => 'Criar Treinamentos NR',
        'operacional.treinamentos.update' => 'Editar Treinamentos NR',
        'operacional.treinamentos.delete' => 'Excluir Treinamentos NR',

        'operacional.anexos.manage'       => 'Gerenciar anexos',
        'operacional.tarefas.manage'      => 'Gerenciar tarefas',
    ],

    // CLIENTE (apenas portal do cliente)
    'cliente' => [
        'cliente.dashboard.view'          => 'Acessar painel do cliente',
        'cliente.funcionarios.view'       => 'Ver funcionários',
        'cliente.funcionarios.create'     => 'Cadastrar funcionários',
        'cliente.funcionarios.update'     => 'Editar funcionários',
        'cliente.funcionarios.toggle'     => 'Ativar/inativar funcionários',

        'cliente.servicos.aso'            => 'Solicitar ASO',
        'cliente.servicos.pgr'            => 'Solicitar PGR',
        'cliente.servicos.pcmso'          => 'Solicitar PCMSO',
        'cliente.servicos.ltcat'          => 'Solicitar LTCAT',
        'cliente.servicos.apr'            => 'Solicitar APR',
        'cliente.servicos.treinamentos'   => 'Solicitar Treinamentos',
    ],
];
