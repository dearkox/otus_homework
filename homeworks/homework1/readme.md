1. Настроил VSCode: установил расширение SFTP от Natizyskunk, сконфигурировал подключение к Timeweb (FTP, хост vh434.timeweb.ru, remotePath /public_html). Git использую через терминал.
2. Установил Битрикс24 корпоративный портал на Timeweb, настроил Push&Pull и обновил до актуальной версии сам Битрикс.
3. Установил Git на Windows 11 (default fast-forward/merge, fs cache).
4. Настроил `user.name` и `user.email`.
5. Сгенерировал SSH-ключ ed25519, добавил публичный ключ в GitHub.
6. В папке `H:\www\OTUS\cc675955.tw1.ru` выполнил `git init`.
7. Привязал удалённый репозиторий: `git remote add origin git@github.com:dearkox/otus_homework.git`.
8. Создал `.gitignore` с правилом игнорирования всего, кроме папки `homeworks/` и самого `.gitignore`.
9. Создал папку `homeworks/homework3/` и файл `readme.md` (этот).
10. Выполнил `git add .`
11. Переименовал ветку в `main`: `git branch -M main`
12. Сделал коммит: `git commit -m "feat: инициализация структуры homeworks/"`
13. Отправил на GitHub: `git push -u origin main`