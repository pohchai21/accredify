# Accredify Assessment

1. [Installation](#Installation)
2. [Entity Relation Diagram](#ERD)
3. [FlowChart](#Flowchart)
4. [Testing](#Testing)


## Installation
1. **Clone the repository:**
    ```sh
    git clone https://github.com/pohchai21/accredify.git
    cd accredify
    ```

2. **Install dependencies:**
    ```sh
    composer install
    ```

3. **Setup environment variables:**
    Copy the `.env.example` to `.env` and configure your database and other environment variables.
    ```sh
    cp .env.example .env
    php artisan key:generate
    ```

4. **Run migrations:**
    ```sh
    php artisan migrate
    php artisan db:seed --class=AddDefaultUserSeeder
    ```
    The seeding will provide user record with the credentials like below
    Email    = testuser1@test.com
    Password = testuser1

5. **Serve the application:**
    ```sh
    php artisan serve
    ```

## ERD
``` mermaid
erDiagram
    user {
        int id
        string name
        string email
        datetime email_verified_at
        string password
        string remember_token
        datetime created_at
        datetime updated_at
    }
    file_verifications {
        int id
        int user_id
        string file_type
        string verification_result
        datetime created_at
        datetime updated_at
    }
    user || --o{uploaded_file: "has many"
```

## Flowchart
```mermaid
    flowchart TD
    A[Login Page] -->|Login Success| B[File Verification Page]
    A -->|Login Failed| A
    B -->|Upload file| C[Check Recipient]
    C -->|Valid Recipient| D[Check Issuer]
    C -->|Invalid Recipient| B
    D -->|Valid Issuer| E[Check Signature]
    D -->|Invalid Issuer| B
    E -->|Valid Signature| F[Verified]
    E -->|Invalid Signature| B
```

### Running Tests

1. **Run tests:**
    ```sh
    php artisan test --filter=FileUploadTest
    ```
