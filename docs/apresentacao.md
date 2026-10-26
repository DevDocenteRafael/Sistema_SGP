# Apresentação do SIPED — roteiro sugerido

## Identificação

- **Projeto:** SIPED — Sistema Integrado Pedagógico.
- **Equipe informada no README:** Lucas Leal, Pedro Comis, Hillary Oliveira, Paloma Leandro e Maria Stephanny.
- **Público:** equipe técnica, responsáveis pedagógicos e avaliadores.
- **Duração sugerida:** 8 a 12 minutos, com demonstração local controlada.

## Fala de abertura

O SIPED é um sistema interno do SENAC DF para gestão pedagógica, portfólio de cursos e processos educacionais. A solução combina uma API Laravel com uma SPA Vue e reúne módulos de portfólio, processos, indicadores, importações, relatórios e ferramentas de apoio. O acesso e as operações são condicionados ao perfil do usuário.

## Sequência de apresentação

1. Apresentar o nome do projeto, equipe e problema operacional atendido.
2. Explicar brevemente a arquitetura: front Vue/Vite, API Laravel, banco configurável e integração SVT opcional.
3. Mostrar a documentação entregue: guias de usuário e técnico, visão do banco, rotas da API, checklist de produção e integração SVT.
4. Demonstrar uma sessão local usando apenas dados de desenvolvimento: entrar, navegar conforme o perfil, consultar um módulo e seu ciclo, abrir filtros/relatório e mostrar a prévia de uma importação sem confirmar alterações desnecessárias.
5. Explicar os resultados de qualidade: build do front, 247 testes PHP e cinco scripts Node aprovados nesta revisão.
6. Registrar correções: fallback de CORS local quando a variável vem vazia e geração automática dos fixtures de importação no bootstrap do PHPUnit.
7. Encerrar com limitações e próximos passos de homologação, sem declarar aprovação antecipada.

## Preparação da demonstração

- Executar `local-start.cmd` apenas em banco local de demonstração.
- Confirmar que o banco é o ambiente local e que nenhum dado real será alterado.
- Usar conta de demonstração local conforme o README; não inserir senha em slides, gravações ou material público.
- Verificar previamente os serviços locais e portas 8000/5173.
- Fazer backup do ambiente de demonstração se houver dados que precisem ser preservados.
- Se o backend ou banco não estiver disponível, apresentar a arquitetura e as evidências automatizadas sem simular operação real.

## Evidências já obtidas nesta revisão

- PHPUnit: 247 testes e 2.641 assertions aprovados, em SQLite em memória.
- Frontend: cinco scripts de teste Node aprovados.
- Build Vite: compilação concluída sem erro.
- Navegador: página de login carregou. O backend não estava ativo; portanto, autenticação e módulos protegidos não foram demonstrados nesta sessão.
- Responsividade: a ferramenta não aplicou o viewport de 390 px solicitado. Não apresentar a responsividade móvel como homologada.

## Aprovações e transparência

Esta apresentação é um roteiro, não um registro de apresentação já realizada. A equipe deve adicionar data, participantes, versão/commit demonstrado, evidências e decisões após a apresentação. A aprovação interna e a homologação dependem dos responsáveis do projeto.
