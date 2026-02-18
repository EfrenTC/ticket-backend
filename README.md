# TicketManager

## ¿De qué va este proyecto?

TicketManager es una plataforma full-stack para **gestión de gastos y tickets**. Permite registrar tickets, categorizarlos, etiquetarlos, conciliarlos con extractos bancarios, controlar presupuestos, crear metas de ahorro, generar reportes y mantener copias de seguridad de la información del usuario.

El proyecto está dividido en:

- **Backend API** (Laravel) con autenticación y lógica de negocio.
- **Frontend SPA** (Vue 3 + TypeScript) para la operación diaria de usuarios.

---

## Tecnologías utilizadas

### Backend

- PHP 8.x
- Laravel 12
- Laravel Sanctum (autenticación)
- Eloquent ORM
- MySQL 8
- MongoDB (contenedor disponible en entorno Docker)
- Docker + Docker Compose

### Frontend

- Vue 3 (Composition API)
- TypeScript
- Vite
- Vue Router
- Axios
- Tailwind CSS

---

## ¿Qué se puede hacer?

### Autenticación y perfil

- Registro e inicio de sesión
- Perfil de usuario con preferencias (moneda, idioma, formato de fecha, modo oscuro, etc.)
- Cambio de contraseña

### Tickets y organización

- CRUD de tickets
- Filtros y búsqueda avanzada (texto, fechas, categoría, estado, etiqueta, orden)
- Relación de tickets con categorías y etiquetas
- Conciliación individual y masiva

### Finanzas y seguimiento

- Gestión de presupuestos por mes/año (globales o por categoría)
- Alertas de consumo de presupuesto (`ok`, `near_limit`, `exceeded`)
- Metas de ahorro con cálculo de progreso

### Reportes

- Comparativas de gasto (mes actual vs mes anterior / año anterior)
- Evolución diaria, por categoría y por día de semana
- Calendario de gastos
- Resumen semanal o mensual
- Exportación de reportes en CSV/XLS

### Productividad y mantenimiento

- Recordatorios (in-app/email) con marcado de lectura
- Widgets personalizables del dashboard
- Backup y restauración de datos de usuario
- Importación de CSV bancario para conciliación

---

## Buenas prácticas aplicadas

- **Arquitectura por capas en backend**: rutas, controladores, modelos y validación por request.
- **Validación robusta** de datos de entrada en endpoints críticos.
- **Control de acceso por usuario** en recursos (ownership checks).
- **Modelado relacional claro** con claves foráneas y tabla pivote (`tag_ticket`).
- **Migrations versionadas** para evolución del esquema.
- **Contenerización** del entorno (app + mysql + mongo) para reproducibilidad.
- **Frontend tipado con TypeScript** para reducir errores de integración API/UI.
- **Composables reutilizables** (`useAuth`, `useTickets`) para lógica compartida.
- **Navegación protegida** por estado de autenticación.
- **Formato de respuesta paginada** en listados grandes para escalabilidad.
- **Operaciones sensibles con confirmación** (eliminaciones, restauración, conciliación masiva).

---

## Diagrama de clases (dominio principal)

```mermaid
classDiagram
		class User {
			+id: bigint
			+name: string
			+email: string
			+password: string
			+currency: string
			+language: string
			+dark_mode: bool
			+report_frequency: enum
			+budget_alerts_enabled: bool
		}

		class Ticket {
			+id: bigint
			+user_id: bigint
			+category_id: bigint?
			+gasto: string
			+importe: decimal
			+categoria: string
			+cif: string
			+metodo_pago: enum
			+conciliado: enum
			+conciliado_en: datetime?
			+referencia_bancaria: string?
			+fecha: date
		}

		class Category {
			+id: bigint
			+user_id: bigint
			+name: string
			+color: string
			+icon: string?
		}

		class Tag {
			+id: bigint
			+user_id: bigint
			+name: string
		}

		class Budget {
			+id: bigint
			+user_id: bigint
			+category_id: bigint?
			+amount: decimal
			+month: int
			+year: int
		}

		class SavingsGoal {
			+id: bigint
			+user_id: bigint
			+title: string
			+target_amount: decimal
			+start_date: date
			+end_date: date
			+is_completed: bool
		}

		class Reminder {
			+id: bigint
			+user_id: bigint
			+ticket_id: bigint?
			+type: enum
			+title: string
			+message: string
			+send_at: datetime?
			+read_at: datetime?
		}

		class UserDashboardWidget {
			+id: bigint
			+user_id: bigint
			+widget_key: string
			+position: int
			+enabled: bool
			+settings: json?
		}

		User "1" --> "*" Ticket : owns
		User "1" --> "*" Category : owns
		User "1" --> "*" Tag : owns
		User "1" --> "*" Budget : owns
		User "1" --> "*" SavingsGoal : owns
		User "1" --> "*" Reminder : owns
		User "1" --> "*" UserDashboardWidget : owns

		Category "1" --> "*" Ticket : classifies
		Category "1" --> "*" Budget : scope
		Ticket "*" --> "*" Tag : tagged_with
		Ticket "1" --> "*" Reminder : related_to
```

---

## Diagrama de base de datos (ERD)

```mermaid
erDiagram
		USERS ||--o{ TICKETS : has
		USERS ||--o{ CATEGORIES : has
		USERS ||--o{ TAGS : has
		USERS ||--o{ BUDGETS : has
		USERS ||--o{ SAVINGS_GOALS : has
		USERS ||--o{ REMINDERS : has
		USERS ||--o{ USER_DASHBOARD_WIDGETS : has

		CATEGORIES ||--o{ TICKETS : categorizes
		CATEGORIES ||--o{ BUDGETS : scopes

		TICKETS ||--o{ TAG_TICKET : links
		TAGS ||--o{ TAG_TICKET : links

		TICKETS ||--o{ REMINDERS : triggers

		USERS {
			bigint id PK
			string name
			string email
			string password
			string avatar_url
			string currency
			string date_format
			string language
			boolean dark_mode
			string report_frequency
			boolean budget_alerts_enabled
			datetime created_at
			datetime updated_at
		}

		TICKETS {
			bigint id PK
			bigint user_id FK
			bigint category_id FK
			string gasto
			decimal importe
			string categoria
			string cif
			string metodo_pago
			string conciliado
			datetime conciliado_en
			string referencia_bancaria
			date fecha
			datetime created_at
			datetime updated_at
		}

		CATEGORIES {
			bigint id PK
			bigint user_id FK
			string name
			string color
			string icon
			datetime created_at
			datetime updated_at
		}

		TAGS {
			bigint id PK
			bigint user_id FK
			string name
			datetime created_at
			datetime updated_at
		}

		TAG_TICKET {
			bigint tag_id FK
			bigint ticket_id FK
			datetime created_at
			datetime updated_at
		}

		BUDGETS {
			bigint id PK
			bigint user_id FK
			bigint category_id FK
			decimal amount
			int month
			int year
			datetime created_at
			datetime updated_at
		}

		SAVINGS_GOALS {
			bigint id PK
			bigint user_id FK
			string title
			decimal target_amount
			date start_date
			date end_date
			boolean is_completed
			datetime created_at
			datetime updated_at
		}

		REMINDERS {
			bigint id PK
			bigint user_id FK
			bigint ticket_id FK
			string type
			string title
			string message
			datetime send_at
			datetime read_at
			datetime created_at
			datetime updated_at
		}

		USER_DASHBOARD_WIDGETS {
			bigint id PK
			bigint user_id FK
			string widget_key
			int position
			boolean enabled
			json settings
			datetime created_at
			datetime updated_at
		}
```

---

## Notas de ejecución rápida

### Backend

```bash
docker compose up -d
docker compose exec app php artisan migrate --force
```

### Frontend

```bash
npm install
npm run dev
```

