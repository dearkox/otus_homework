# Домашнее задание #12: Собственные обработчики REST

### Цель:
- систематизация обработки входящих данных;
- написание CRUD сервисов;
- правильное проектирование.

### Описание/Пошаговая инструкция выполнения домашнего задания:
Создать обработчики для своей сущности в системе, с точками входа CRUD.

### Операции:
- Создание
- Редактирование
- Удаление
- Чтение
- Получение списка записей

## Решение

Реализовал свои REST-методы для сущности `Заметки` через событие `OnRestServiceBuildDescription`.
Сущность простая — таблица `otus_notes` с полями `ID`, `TITLE`, `TEXT`, `CREATED_BY`, `CREATED_AT`, `UPDATED_AT`.
Для неё написал ORM-класс и пять CRUD-обработчиков: `add`, `update`, `delete`, `get`, `list`.

Регистрация методов в `events.php` через `OnRestServiceBuildDescription`. Обработчики унаследованы от `\IRestService`, списочный метод поддерживает пагинацию.
Добавил логирование всех операций в `rest.log` через `Debug::writeToFile`. Все ошибки пробрасываются через `RestException` с локализованными сообщениями.

Автозагрузка для классов в `/local/rest/` настроена отдельно. Создал языковой файл для scope `otus.notes` и сообщений об ошибках.

Для тестирования создан входящий вебхук с правами `otus.notes`. Все методы проверены через `curl` и Postman. Список методов:
- `otus.notes.add` — создание заметки
- `otus.notes.update` — обновление заметки
- `otus.notes.delete` — удаление заметки
- `otus.notes.get` — получение одной заметки
- `otus.notes.list` — получение списка с фильтрацией


## Сущность "Заметки"

Для демонстрации работы CRUD-методов создана сущность **"Заметки"** с таблицей `otus_notes`.

| Поле | Тип | Описание |
|------|-----|----------|
| `ID` | int | Первичный ключ, автоинкремент |
| `TITLE` | string(255) | Название заметки (обязательное) |
| `TEXT` | text | Текст заметки |
| `CREATED_BY` | int | ID пользователя-создателя |
| `CREATED_AT` | datetime | Дата создания |
| `UPDATED_AT` | datetime | Дата обновления |

```SQL
CREATE TABLE IF NOT EXISTS otus_notes (
ID INT AUTO_INCREMENT PRIMARY KEY,
TITLE VARCHAR(255) NOT NULL,
TEXT TEXT,
CREATED_BY INT NOT NULL,
CREATED_AT DATETIME NOT NULL,
UPDATED_AT DATETIME NOT NULL
);
```

## REST-методы

| Метод | Описание | Параметры |
|-------|----------|-----------|
| `otus.notes.add` | Создание заметки | `fields[TITLE]` (обяз.), `fields[TEXT]` |
| `otus.notes.update` | Обновление заметки | `fields[ID]` (обяз.), `fields[TITLE]`, `fields[TEXT]` |
| `otus.notes.delete` | Удаление заметки | `id` (обяз.) |
| `otus.notes.get` | Получение одной заметки | `id` (обяз.) |
| `otus.notes.list` | Получение списка с фильтрацией | `filter`, `order`, `start` |

## Файловая структура
```
/local/rest/notes/
├── ORM/
│   └── NotesTable.php                    # ORM-класс таблицы otus_notes
├── Handlers/
│   ├── NotesRestHandler.php              # Регистрация REST-методов и событий
│   ├── NotesCrudHandler.php              # CRUD-обработчики с логированием
│   └── NotesEventHandler.php             # Подготовка данных для REST-событий
└── lang/ru/
└── index.php                         # Локализация scope и сообщений

/local/php_interface/
├── init.php                              # Подключение автозагрузчиков
└── events.php                            # Регистрация OnRestServiceBuildDescription

/local/logs/
└── rest.log                              # Лог-файл
```
