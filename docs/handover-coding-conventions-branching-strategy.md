# kpn-pm - Handover Document: Coding Conventions & Branching Strategy

**Project Name:** kpn-pm  
**Tech Stack:** Laravel 11 + PHP 8.2 + Blade + Eloquent + Vite + Tailwind CSS + Bootstrap + JavaScript

---

## 1. Coding Conventions

### A. Backend Environment
- **Architecture & Design Patterns:** This project follows a standard MVC structure with a service-layer pattern for reusable business logic. Business logic should be placed in the app/Services folder, while controllers live in app/Http/Controllers and models live in app/Models.
- **Naming Conventions:**
  - **Variables & Functions:** Uses camelCase. Example: the controller in app/Http/Controllers/TeamGoalController.php uses $filterYear and index(Request $request).
  - **Classes & Controllers:** Uses PascalCase. Example: TeamGoalController and AppService.
  - **Database Tables:** Uses snake_case and plural table names. Example: the migration in database/migrations/2024_04_03_034526_create_goals_table.php creates the goals table.
- **Code Formatting:** Code styling is enforced via Laravel Pint, which is declared in composer.json. Run ./vendor/bin/pint before committing.

### B. Frontend Environment
- **Component Structure:** Reusable Blade components are stored in resources/views/components, while page-specific screens are organized under resources/views/pages and shared layouts under resources/views/layouts.
- **Naming Conventions:**
  - **Component Files:** Uses lowercase Blade file names such as primary-button.blade.php.
  - **State & Functions:** JavaScript uses camelCase, as seen in frontend code such as const formattedKey = label.replace(/\s+/g, "");.
- **Linter & Formatter:** No dedicated ESLint or Prettier configuration is present in the repo; frontend assets are built and bundled via Vite and Tailwind.

### C. API Standards & Documentation
- **API Response Structure:** The most common API response pattern in this project is:

```json
{
  "status": "success",
  "data": {}
}
```

- **Error Response Pattern:**

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {}
}
```

- This pattern is visible in controllers such as app/Http/Controllers/FormAppraisalController.php.

---

## 2. Git & Branching Strategy

- **Repository Branch Context:** The repository context shows the current working branch as master and the default branch as main.
- **Branching Practice:** No formal branch naming policy or CI/CD workflow file was found in the repository. There is no checked-in GitHub Actions or GitLab CI configuration.
- **Recommended Working Pattern:** Create short-lived feature or fix branches from main/master, keep commits focused, and merge through pull requests when the team workflow requires review.
- **Code Quality Gate:** Before merging, run backend formatting with ./vendor/bin/pint and verify the frontend build with npm run build.
