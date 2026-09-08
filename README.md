# Subdrill

## Login isolado por aplicação

Este repositório contém diretamente os arquivos que devem ser publicados dentro de
`public_html/subdrill/`; ele **não** cria nem versiona a pasta `public_html/`.
Copie `.env.example` para o `.env` já existente nesse diretório e configure:

```env
APP_SESSION_NAME=subdrill_session
APP_BASE_PATH=/subdrill
```

`APP_SESSION_NAME` precisa ser único para cada sistema no mesmo domínio (por
exemplo, `outroapp_session` no OutroApp). Além disso, o cookie é enviado somente
para `APP_BASE_PATH/`, impedindo que uma sessão em `/subdrill/` substitua ou leia a
sessão de `/outroapp/`. O `.env` continua ignorado pelo Git.

Importe `database/schema.sql` antes de cadastrar o primeiro usuário. Para criar
uma senha segura, gere o hash no servidor: `php -r "echo password_hash('sua-senha', PASSWORD_DEFAULT), PHP_EOL;"`.

Aplicação web baseada em **PHP + JavaScript + HTML + MySQL**, utilizando uma arquitetura **MPA (Multi-Page Application)** com **Front Controller**, **Routing/Dispatcher** e **App Shell com Sidebar persistente**.

O projeto prioriza simplicidade, baixo overhead, separação de responsabilidades e segurança dos dados armazenados.

---

## Stack

- **Frontend:** HTML + JavaScript
- **Estilização:** TailwindCSS, sempre em dark mode.
- **Backend:** PHP
- **Banco de dados:** MySQL
- **Configuração:** `public_html/subdrill/.env`
- **Arquitetura:** Front Controller + Routing/Dispatcher
- **Modelo de aplicação:** MPA (Multi-Page Application)
- **IA / geração de texto:** Google Gemini API
- **TTS:** Google Cloud Text-to-Speech API

---

# 1. Arquitetura

A aplicação utiliza o padrão **Front Controller**, combinado com **Routing/Dispatcher**.

O arquivo:

```text
public_html/subdrill/app.index
```

é o ponto central de entrada e funciona como **Front Controller** da aplicação.

Fluxo principal:

```text
Browser
   │
   ▼
app.index
   │
   ▼
Router
   │
   ▼
Dispatcher
   │
   ├── /             → página HTML
   ├── /login        → login.html
   ├── /dashboard    → dashboard.html
   └── /api/...      → endpoint PHP
```

A arquitetura pode ser resumida como:

```text
Front Controller
       ↓
    Router
       ↓
  Dispatcher
       ↓
Static HTML Pages
       ↓
 JavaScript
       ↓
   PHP API
       ↓
    MySQL
```

O modelo de aplicação é **MPA (Multi-Page Application)**.

---

# 2. Estrutura de diretórios

O projeto está localizado em:

```text
public_html/subdrill/
```

Estrutura base:

```text
subdrill/
│
├── app.index
│
├── index.php
│
├── pages/
│   ├── login.html
│   ├── dashboard.html
│   └── ...
│
├── api/
│   ├── auth/
│   ├── users/
│   ├── crud/
│   ├── ai/
│   ├── tts/
│   └── ...
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── icons/
│
└── ...
```

O arquivo `.env` deve ficar preferencialmente **fora do `public_html`**, evitando que as credenciais possam ser acessadas diretamente pela web.

Exemplo:

```text
/home/usuario/
└── .env

public_html/
└── subdrill/
    ├── app.index
    ├── index.php
    ├── pages/
    ├── api/
    └── assets/
```

---

# 3. Front Controller

O `app.index` é responsável por receber as requisições e encaminhá-las para o destino correto.

Exemplo conceitual:

```php
<?php

$route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($route) {

    case '/':
        require __DIR__ . '/pages/index.html';
        break;

    case '/login':
        require __DIR__ . '/pages/login.html';
        break;

    case '/dashboard':
        require __DIR__ . '/pages/dashboard.html';
        break;

    default:
        http_response_code(404);
        require __DIR__ . '/pages/404.html';
        break;
}
```

O Front Controller não deve concentrar regras de negócio.

Sua responsabilidade principal é:

- receber a requisição;
- identificar a rota;
- encaminhar a requisição;
- controlar o fluxo de acesso quando necessário;
- entregar a página ou endpoint correspondente.

---

# 4. Routing / Dispatcher

O Router identifica a rota solicitada:

```text
/
 /login
 /dashboard
 /cruds
 /settings
 /api/...
```

O Dispatcher determina o destino da requisição.

Exemplo:

```text
/login
   ↓
pages/login.html

/dashboard
   ↓
pages/dashboard.html

/api/users
   ↓
api/users/...
```

A separação conceitual é:

```text
Router
  → identifica a rota

Dispatcher
  → determina o handler/destino

Handler/API/Page
  → executa a operação
```

---

# 5. MPA — Multi-Page Application

O Subdrill utiliza o modelo **MPA (Multi-Page Application)**.

Cada rota pode representar uma página independente:

```text
/login
    ↓
login.html

/dashboard
    ↓
dashboard.html

/cruds
    ↓
cruds.html
```

A aplicação não depende de uma SPA para funcionar.

JavaScript é utilizado de forma progressiva para adicionar interatividade e comunicação com as APIs.

---

# 6. App Shell

As áreas internas da aplicação utilizam um **App Shell com Sidebar persistente**.

Estrutura visual:

```text
┌──────────────────┬──────────────────────────────────┐
│                  │                                  │
│     SIDEBAR      │          MAIN CONTENT            │
│                  │                                  │
│  Meus CRUDs      │       Página atual               │
│  Colunas         │                                  │
│  Configurações   │                                  │
│                  │                                  │
│                  │                                  │
└──────────────────┴──────────────────────────────────┘
```

O App Shell é composto conceitualmente por:

```text
App Shell
├── Sidebar
├── Header
└── Main Content
```

O Sidebar mantém a navegação da aplicação, enquanto a área principal apresenta o conteúdo da rota atual.

---

# 7. HTML

As páginas da aplicação são mantidas em HTML:

```text
pages/
├── login.html
├── dashboard.html
└── ...
```

O HTML é responsável pela estrutura visual da página.

O comportamento interativo é implementado principalmente em JavaScript.

---

# 8. TailwindCSS e Dark Mode

A interface utiliza **TailwindCSS** para estilização.

O sistema deve manter suporte ao **Dark Mode** como padrão visual da aplicação.

A organização dos componentes deve priorizar:

- consistência visual;
- componentes reutilizáveis;
- responsividade;
- acessibilidade;
- estados de interação;
- contraste adequado;
- suporte ao tema escuro.

---

# 9. JavaScript

O JavaScript é responsável pela camada de interação do frontend.

Exemplos:

- formulários;
- modais;
- menus;
- filtros;
- busca;
- notificações;
- atualização da interface;
- chamadas `fetch()`;
- comunicação com APIs;
- reprodução de áudio;
- gerenciamento de estados locais da interface.

Exemplo:

```javascript
fetch('/api/users/list.php')
    .then(response => response.json())
    .then(data => {
        console.log(data);
    });
```

JavaScript **não é uma camada de segurança**.

Toda validação importante deve ser realizada novamente no backend.

---

# 10. API PHP

As operações dinâmicas são realizadas por endpoints PHP dentro de:

```text
api/
```

Exemplo:

```text
api/
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── register.php
│
├── users/
│   ├── list.php
│   ├── create.php
│   ├── update.php
│   └── delete.php
│
├── ai/
│   └── generate.php
│
├── tts/
│   └── generate.php
│
└── ...
```

Fluxo:

```text
HTML
  │
  ▼
JavaScript
  │
  ▼
fetch()
  │
  ▼
PHP API
  │
  ▼
MySQL / serviços externos
  │
  ▼
JSON
  │
  ▼
JavaScript
  │
  ▼
Interface
```

---

# 11. Banco de dados MySQL

O banco utilizado pela aplicação é **MySQL**.

As credenciais são fornecidas pelo arquivo `.env`:

```env
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=
```

A conexão deve utilizar PDO e prepared statements.

Exemplo:

```php
<?php

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] .
    ';dbname=' . $_ENV['DB_NAME'] .
    ';charset=utf8mb4',
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
```

O usuário MySQL utilizado pela aplicação deve possuir somente os privilégios necessários.

---

# 12. Criptografia dos dados da tabela `users`

Todos os dados armazenados na tabela `users` devem ser tratados como dados protegidos e armazenados de forma criptografada conforme a estratégia definida pela aplicação.

A chave de descriptografia deve permanecer no `.env` e **não deve ser armazenada no banco de dados**.

Exemplo:

```env
APP_ENCRYPTION_KEY=
```

A aplicação deve carregar a chave por variável de ambiente.

A chave nunca deve:

- aparecer no HTML;
- ser enviada ao navegador;
- ser incluída em JavaScript;
- ser retornada pela API;
- ser gravada em logs;
- ser versionada no Git;
- ser armazenada junto com os dados criptografados no MySQL.

---

## 12.1 Criptografia autenticada

A implementação deve utilizar uma biblioteca criptográfica apropriada, preferencialmente **Libsodium**, utilizando um esquema de criptografia autenticada.

O objetivo é garantir:

```text
Confidencialidade
+
Integridade
+
Autenticidade do ciphertext
```

Cada valor criptografado deve possuir um nonce/IV apropriado e único para a operação.

Não utilizar:

```text
MD5
SHA-1
SHA-256 como criptografia
Base64 como criptografia
```

Base64 é apenas uma codificação e não fornece confidencialidade.

---

## 12.2 Senhas

Senhas possuem uma regra diferente.

**Senhas não devem ser criptografadas para posterior recuperação.**

Devem ser armazenadas utilizando password hashing:

```php
$hash = password_hash($password, PASSWORD_DEFAULT);
```

E verificadas com:

```php
password_verify($password, $hash);
```

O sistema não deve possuir nenhuma funcionalidade que permita recuperar a senha original.

A distinção é:

```text
Senha
  ↓
Password Hash
  ↓
password_hash()

Dado que precisa ser recuperado
  ↓
Criptografia autenticada
  ↓
Libsodium
```

---

# 13. Chave de criptografia

A chave principal utilizada para descriptografia fica no `.env`.

Exemplo:

```env
APP_ENCRYPTION_KEY=
```

O `.env` deve possuir permissões restritas e permanecer fora do diretório público sempre que possível.

Exemplo:

```text
/home/usuario/.env
```

e não:

```text
/public_html/subdrill/.env
```

Caso a infraestrutura disponibilize um Secret Manager/KMS, ele é preferível ao armazenamento direto da chave em arquivo `.env`.

---

# 14. Geração de respostas em texto — Gemini

A aplicação utiliza a API do **Google Gemini** para geração de respostas em texto.

A API Key deve ficar no `.env`:

```env
GEMINI_API_KEY=
```

Configuração:

```php
define(
    'GEMINI_API_KEY',
    envValue('GEMINI_API_KEY', '')
);

define(
    'GEMINI_API_URL',
    envValue(
        'GEMINI_API_URL',
        'https://generativelanguage.googleapis.com/v1beta/models'
    )
);

define(
    'GEMINI_TRANSLATION_MODEL',
    envValue(
        'GEMINI_TRANSLATION_MODEL',
        'gemini-3.5-flash-lite'
    )
);
```

A chave nunca deve ser exposta ao frontend.

O fluxo correto é:

```text
Browser
   │
   ▼
JavaScript
   │
   ▼
PHP API
   │
   ▼
Gemini API
   │
   ▼
PHP
   │
   ▼
Browser
```

O navegador não deve chamar diretamente a API do Gemini utilizando a chave privada da aplicação.

---

# 15. Geração de áudio TTS

A aplicação utiliza **Google Cloud Text-to-Speech** para geração de áudio.

A API Key deve ficar no `.env`:

```env
GOOGLE_CLOUD_API_KEY=
```

Configuração:

```php
define(
    'GOOGLE_CLOUD_API_KEY',
    envValue('GOOGLE_CLOUD_API_KEY', '')
);
```

A chave nunca deve ser disponibilizada ao cliente.

Fluxo:

```text
Browser
   │
   ▼
JavaScript
   │
   ▼
PHP API
   │
   ▼
Google Cloud TTS
   │
   ▼
Áudio
   │
   ▼
PHP
   │
   ▼
MySQL
```

---

# 16. Vozes TTS

Voz configurada para português:

```text
pt-BR-Chirp3-HD-Algenib
```

Voz configurada para inglês:

```text
en-GB-Chirp3-HD-Achird
```

Esses identificadores devem permanecer centralizados na configuração da aplicação sempre que possível.

---

# 17. Armazenamento de áudios e imagens

Áudios e imagens gerados ou enviados pela aplicação devem ser convertidos para **Base64** e armazenados no MySQL.

Fluxo:

```text
Arquivo
   │
   ▼
Binary Data
   │
   ▼
Base64
   │
   ▼
MySQL
```

Para recuperação:

```text
MySQL
   │
   ▼
Base64
   │
   ▼
Binary Data
   │
   ▼
Response / HTML / Audio
```

Exemplo conceitual:

```php
$base64 = base64_encode($binaryData);
```

Para reconstrução:

```php
$binaryData = base64_decode($base64, true);
```

O MIME type deve ser armazenado juntamente com o conteúdo para permitir a correta reconstrução do arquivo.

Exemplo de estrutura:

```text
media
├── id
├── mime_type
├── data_base64
├── size_bytes
└── created_at
```

---

## 17.1 Considerações sobre Base64

Base64 aumenta o tamanho dos dados armazenados em comparação com o binário original.

Portanto:

- validar tamanho máximo dos arquivos;
- evitar arquivos desnecessariamente grandes;
- considerar compressão quando apropriado;
- utilizar `MEDIUMTEXT`/`LONGTEXT` ou estrutura adequada ao volume esperado;
- avaliar `BLOB` caso a implementação futura permita abandonar Base64.

A decisão de armazenar Base64 no banco é uma exigência arquitetural deste projeto.

---

# 18. Segurança das APIs

Todas as APIs devem:

- validar o método HTTP;
- validar os parâmetros recebidos;
- validar autenticação;
- validar autorização;
- utilizar prepared statements;
- evitar SQL concatenado;
- validar uploads;
- limitar tamanho de payload;
- retornar JSON consistente;
- não expor stack traces em produção;
- não retornar credenciais ou chaves;
- registrar somente informações necessárias em logs.

---

# 19. Sessão e autenticação

A autenticação deve ser realizada no backend.

O fluxo esperado:

```text
Login HTML
    │
    ▼
JavaScript
    │
    ▼
POST /api/auth/login
    │
    ▼
PHP
    │
    ├── busca usuário
    ├── verifica password hash
    └── cria sessão
             │
             ▼
          Dashboard
```

Cookies de sessão devem utilizar, quando aplicável:

```text
Secure
HttpOnly
SameSite
```

O frontend não deve armazenar credenciais ou chaves criptográficas privadas.

---

# 20. HTTPS

A aplicação deve operar utilizando **HTTPS**.

Isso protege:

```text
Browser
   ⇅
Servidor
```

contra exposição dos dados durante o transporte.

A criptografia no banco e o HTTPS possuem objetivos diferentes:

```text
HTTPS
→ protege os dados durante o transporte

Criptografia no banco
→ protege os dados armazenados

Password Hash
→ protege senhas armazenadas
```

As três camadas devem ser tratadas separadamente.

---

# 21. Arquivo `.env`

Exemplo de configuração:

```env
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=

APP_ENCRYPTION_KEY=

GEMINI_API_KEY=
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models
GEMINI_TRANSLATION_MODEL=gemini-3.5-flash-lite

GOOGLE_CLOUD_API_KEY=
```

O arquivo `.env` não deve ser commitado:

```gitignore
.env
```

Nunca colocar valores reais de produção no repositório.

---

# 22. Variáveis de configuração

A aplicação deve centralizar o carregamento das variáveis de ambiente.

Exemplo:

```php
define('GEMINI_API_KEY', envValue('GEMINI_API_KEY', ''));

define(
    'GEMINI_API_URL',
    envValue(
        'GEMINI_API_URL',
        'https://generativelanguage.googleapis.com/v1beta/models'
    )
);

define(
    'GEMINI_TRANSLATION_MODEL',
    envValue(
        'GEMINI_TRANSLATION_MODEL',
        'gemini-3.5-flash-lite'
    )
);

define(
    'GOOGLE_CLOUD_API_KEY',
    envValue('GOOGLE_CLOUD_API_KEY', '')
);
```

O código da aplicação não deve conter chaves secretas diretamente.

---

# 23. Performance

A arquitetura prioriza baixo overhead.

Principais estratégias:

- HTML separado da lógica pesada;
- PHP utilizado principalmente para processamento e APIs;
- JavaScript somente quando necessário;
- PDO com prepared statements;
- índices apropriados no MySQL;
- evitar N+1 queries;
- OPcache;
- compressão HTTP;
- cache de arquivos estáticos;
- minimizar bundles JavaScript;
- limitar consultas desnecessárias;
- evitar processamento de arquivos excessivamente grandes.

Fluxo:

```text
                    Browser
                       │
                       ▼
                 Static HTML
                       │
                       ▼
                  JavaScript
                       │
             ┌─────────┴─────────┐
             │                   │
          Interface           PHP API
                                 │
                     ┌───────────┴───────────┐
                     │                       │
                   MySQL               APIs externas
```

---

# 24. Separação de responsabilidades

A aplicação deve seguir uma separação clara:

```text
app.index
    → Front Controller
    → Routing
    → Dispatching

pages/
    → HTML / interface

assets/
    → CSS / JavaScript / imagens públicas

api/
    → endpoints PHP
    → autenticação
    → regras de aplicação
    → acesso a serviços externos
    → acesso ao banco

MySQL
    → persistência

.env
    → segredos e configuração
```

O `app.index` não deve conter regras complexas de negócio.

---

# 25. Fluxo completo

Uma requisição de página:

```text
Browser
   │
   ▼
app.index
   │
   ▼
Router
   │
   ▼
Dispatcher
   │
   ▼
HTML
   │
   ▼
Browser
```

Uma operação dinâmica:

```text
Browser
   │
   ▼
JavaScript
   │
   ▼
PHP API
   │
   ├──────────────► MySQL
   │
   └──────────────► Gemini / Google Cloud
   │
   ▼
JSON
   │
   ▼
JavaScript
   │
   ▼
Interface
```

---

# 26. Resumo da arquitetura

O Subdrill utiliza:

```text
MPA
│
├── Front Controller
│      └── app.index
│
├── Router
│
├── Dispatcher
│
├── Static HTML Pages
│
├── App Shell
│      ├── Sidebar
│      ├── Header
│      └── Main Content
│
├── JavaScript
│
├── PHP API
│
├── MySQL
│
├── Criptografia de dados
│      └── chave no .env
│
├── Gemini
│      └── geração de texto
│
└── Google Cloud TTS
       └── geração de áudio
```

### Definição

> **Subdrill é uma aplicação MPA baseada em HTML, JavaScript e PHP, utilizando um Front Controller central (`app.index`) combinado com Routing/Dispatcher, App Shell com Sidebar persistente, APIs PHP para operações dinâmicas, MySQL para persistência e integração com Gemini e Google Cloud TTS para geração de conteúdo. Dados sensíveis são protegidos por criptografia e as credenciais da aplicação permanecem fora do código-fonte, em variáveis de ambiente.**
