# Atlas HRM Mobile App

## Local API Configuration
Use a local dart-define file so API host is stable and not hardcoded in source.

1. Copy example file:
```bash
cp env/local.example.json env/local.json
```
2. Update `env/local.json` with your machine IP:
```json
{
  "API_BASE_URL": "http://<your-lan-ip>:8000/api"
}
```
3. Run app:
```bash
flutter run --dart-define-from-file=env/local.json
```

Notes:
- Android emulator default (without define): `http://10.0.2.2:8000/api`
- iOS simulator default (without define): `http://127.0.0.1:8000/api`
- Physical iPhone can set server once in app: **Login -> API Settings** and enter `http://<your-lan-ip>:8000/api`.
