# Лекція 5: Eloquent ORM

## 💬 Фрази до уроку (словник)

> Це слова, які ви будете чути на кожній парі. 
> Вчіть — не соромно не знати, соромно не питати.

| Термін               | Вимова/Скорочення | Що означає простою мовою |
|----------------------|------------------|--------------------------|
| **ORM**              | О-ер-ем | Object-Relational Mapping — перекладач між PHP-об'єктами та SQL-таблицями |
| **Model**            | модель | PHP клас, який "представляє" таблицю в БД |
| **Migration**        | міграція | "план" структури таблиці (вже вивчили в Лекції 4) |
| **Relationship**     | ріліейшнщіп | Зв'язок між таблицями (один-до-багатьох тощо) |
| **belongsTo**        | белонгс ту | "Я належу до..." (пост належить до юзера) |
| **hasMany**          | хез мені | "У мене є багато..." (юзер має багато постів) |
| **hasOne**           | хез ван | "У мене є один..." (юзер має один профіль) |
| **belongsToMany**    | белонгс ту мені | Many-to-many через pivot таблицю |
| **Eager Loading**    | ігер лоудінг | Завантажити все одразу, щоб не бігати в БД 100 разів |
| **Lazy Loading**     | лейзі лоудінг | Завантажити зв'язані дані тільки коли вони потрібні |
| **Mass Assignment**  | мас есайнмент | Заповнення моделі з масиву даних (потребує `$fillable`) |
| **Timestamp**        | тайместемп | `created_at` та `updated_at` — Laravel додає автоматично |

---

## 1. Моделі: знайомство з Eloquent

### Створення моделі

```bash
# Тільки модель
php artisan make:model Post

# Модель + міграція (найчастіший варіант)
php artisan make:model Post -m

# Модель + міграція + контролер + factory + seeder (повний комплект)
php artisan make:model Post -mcfs
```

### Структура моделі

```php
<?php
// app/Models/Post.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory;

    /**
     * За замовчуванням Laravel шукає таблицю з назвою моделі
     * у множині (posts). Якщо назва нестандартна — вказуємо вручну.
     */
    protected $table = 'posts'; // необов'язково якщо назва стандартна

    /**
     * Поля, які можна масово заповнювати (Mass Assignment)
     * Захист від того, щоб юзер не змінив поле is_admin через форму 😈
     */
    protected $fillable = [
        'title',
        'body',
        "author_id",
        "category_id",
        "status"
    ];
}
```

### Конвенції назв (Laravel робить магію сам)

```
Модель називаємо в однині а таблиця в множині
Модель: Post          → Таблиця: posts
Модель: UserProfile   → Таблиця: user_profiles
Модель: BlogPost      → Таблиця: blog_posts
Модель: Category      → Таблиця: categories
```

> 💡 Laravel автоматично перетворює CamelCase в snake_case та ставить у множину (якщо створювати через php artisan).
>    Якщо таблиця названа нестандартно — вкажіть `$table` вручну.

---

## 3. CRUD операції через Eloquent

### CREATE — створення запису

```php
// Спосіб 1: create() — один рядок! (потребує $fillable)
$post = Post::create([
    'title'   => 'Мій перший пост',
    'body' => 'Це вміст поста...',
    'author_id' => 1, 
    'category_id'  => 1,
]);

// Спосіб 2: new + save() (більше контролю)
$post = new Post();
$post->body   = 'Мій перший пост';
$post->author_id = 1;
$post->category_id = 1;
$post->save(); // ← тут відбувається INSERT INTO posts ...

// Ще є інші сбособи створювати записи в базу данних але зараз зупинимось на цьому
```

### READ — читання даних

```php
// Отримати ВСЕ (обережно на великих таблицях!)
$posts = Post::all();

// Знайти по ID (кидає виняток якщо не знайдено)
$post = Post::find(5);

// Знайти по ID (повертає null якщо не знайдено)
$post = Post::find(10000);

// Перший що відповідає умові,
$post = Post::where('author_id', 1)->firstOrFail();
$post = Post::where('author_id', 1)->first();

// Всі записи що відповідають умові,
$posts = Post::where('author_id', 1)->get();

// Отримати тільки певні колонки
$posts = Post::select('id', 'title', 'created_at')->get();

// Фільтрація, викоростувуєм декілька where
$publishedPosts = Post::where('status', 'published')
                      ->where('author_id', 1)
                      ->get();

// Різні оператори where
Post::where('category_id', '>', 100)->get();        // більше
Post::where('body', 'like', '%Laravel%')->get();   // LIKE
Post::whereIn('status', ['published', 'draft'])->get(); // IN
Post::whereBetween('created_at', ['2024-01-01', '2024-12-31'])->get();

// Сортування та обмеження
$newPosts = Post::orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
```

### UPDATE — оновлення запису

```php
// Спосіб 1: знайти і оновити. якщо не знайдемо то буде виключиня (Exception)
$post = Post::findOrFail(5);
$post->title = 'Новий заголовок';
$post->save();

// Спосіб 2: update() з масивом
$post = Post::findOrFail(5);
$post->update([
    'status' => 'published',
]);

// Спосіб 3: масове оновлення (без завантаження моделі)
Post::where('status', 'draft')
    ->where('created_at', '<', now()->subDays(30)) # всі пости що старші 30 днів та чорновики
    ->update(['status' => 'archived']);
```

### DELETE — видалення запису

```php
// Спосіб 1: знайти і видалити
$post = Post::findOrFail(5);
$post->delete();

// Спосіб 2: видалити напряму
Post::destroy(5);        // один запис
Post::destroy([1, 2, 3]); // кілька записів

// Спосіб 3: масове видалення
Post::where('status', 'spam')->delete();
```

---

## 4. Зв'язки між моделями (Relationships)

> 💡 **Ключова думка:** Зв'язки в Eloquent — це просто методи в моделі.
>     Вони повертають спеціальні об'єкти-зв'язки, через які Laravel сам будує 
>     JOIN або окремий запит.

### One-to-Many (один-до-багатьох)

Найпоширеніший зв'язок. Один юзер — багато постів.

```
users                    posts
──────────────           ──────────────────────────
id | name               id | author_id | title
───────────             ──────────────────────────
1  | Макс                1  |    1     | "Мій пост"
2  | Володимир           2  |    1     | "Ще пост"
                         3  |    2     | "Пост Олени"
```

```php
// app/Models/User.php
class User extends Model
{
    // User hasMany Posts (у юзера є багато постів)
    public function posts()
    {
        return $this->hasMany(Post::class);
        // Laravel сам знає, що треба шукати posts.user_id = users.id
    }

    // Тільки опубліковані пости
    public function publishedPosts()
    {
        return $this->hasMany(Post::class)
                    ->where('status', 'published')
                    ->latest();
    }
}

// app/Models/Post.php
class Post extends Model
{
    // Post belongsTo User (пост належить юзеру)
    public function user()
    {
        return $this->belongsTo(User::class);
        // шукає posts.user_id = users.id
    }

    // Якщо ключ нестандартний:
    // return $this->belongsTo(User::class, 'author_id', 'id');
}

// ──── Використання ────
$user = User::find(1);

// Всі пости юзера
$posts = $user->posts()->get();       // пости користувача як метод (можна додати фільтри)
$posts = $user->posts()->where('status', 'published')->get();

// Пост → Юзер
$post = Post::find(1);
$author = $post->user;               // об'єкт User
echo $post->user->name;             // ім'я автора

// Створення пов'язаного запису
$user->posts()->create([
    'title'   => 'Новий пост',
    'content' => 'Вміст...',
    'status'  => 'draft',
]);
```



### Завдання 1: Створити міграцію до таблиці posts

Додайте інт колонку count_view з дефолтним значенням 0 до таблиці posts :

### Завдання 2: Написати звязок (Relations) між моделями Author та Post
Додайте метод posts() з реалізацією звязку в модель Author , метод author() до моделі Post
В контролері [PostController.php](app/Http/Controllers/PostController.php) в методі index()
розкомітити та дописати код для пошуку постів по авторах

---

### Завдання 3: Створити контролер Category
 Реалізувати метод index() PS: Получити всі категорії та вивести їх в dd();
 Реалізувати метод testCreate()
 Результат => водимо path http://localhost:8081/test_category і має бути два числа скільки у нас категорій 

```php
 puclic function testCreate()
 {
    // Cтворити одну категорію з довільним name
    dump(Category::all()->count()); // перевіряємо чи сторили
    // Cтворити 3 категорій з довільним name
    dd(Category::all()->count());
 }
```

## 📊 Шпаргалка Eloquent

### Основні методи

| Метод | Описання | SQL еквівалент |
|-------|---------|---------------|
| `Post::all()` | Всі записи | `SELECT * FROM posts` |
| `Post::find(1)` | За ID | `SELECT * WHERE id = 1` |
| `Post::findOrFail(1)` | За ID або 404 | те саме + виняток |
| `Post::first()` | Перший | `LIMIT 1` |
| `Post::create([])` | Створити | `INSERT INTO` |
| `$post->save()` | Зберегти | `INSERT` або `UPDATE` |
| `$post->update([])` | Оновити | `UPDATE WHERE id = ?` |
| `$post->delete()` | Видалити | `DELETE` або soft delete |
| `Post::where(...)` | Фільтр | `WHERE` |
| `->orderBy(...)` | Сортування | `ORDER BY` |
| `->limit(10)` | Обмеження | `LIMIT 10` |
| `->paginate(15)` | Пагінація | `LIMIT 15 OFFSET ?` |
| `->count()` | Підрахунок | `COUNT(*)` |
| `->exists()` | Перевірка наявності | `SELECT 1 WHERE ... LIMIT 1` |
| `->with(...)` | Eager Loading | Окремий SELECT |
| `->withCount(...)` | Підрахунок зв'язку | `SELECT COUNT(...)` |

### Зв'язки

| Метод | Коли використовувати | Приклад |
|-------|---------------------|---------|
| `hasOne` | У моделі є один зв'язаний | User → UserProfile |
| `hasMany` | У моделі є багато зв'язаних | User → Posts |
| `belongsTo` | Модель належить іншій | Post → User |

---

## ✅ Контрольні питання / Перевіряйте в консолі

1. Що таке ORM і яку проблему він вирішує?
2. Навіщо потрібен `$fillable`? Що станеться без нього при `create([])`?
3. Яка різниця між `Post::find(1)` та `Post::findOrFail(1)`?
4. Як виправити N+1? Що таке Eager Loading?
5. Яка різниця між `hasMany` та `belongsTo`?

---
