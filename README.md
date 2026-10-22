# ShopFlow E-commerce

PHP storefront using PostgreSQL for orders and authoritative inventory, with Firebase Realtime Database for live stock updates.

## Firebase Setup

1. In Firebase Console, open **Project Settings > Service accounts** and generate a private key for the Firebase Admin SDK. Keep the downloaded JSON private.
2. In Realtime Database > **Rules**, apply the contents of `firebase-database.rules.json` and publish them. This allows public inventory reads while blocking client writes; the PHP Admin SDK writes using its service account.
3. In Render, open the web service's **Environment** settings and add:
	- `FIREBASE_DATABASE_URL`: `https://shopflow-4c4e9-default-rtdb.asia-southeast1.firebasedatabase.app`
	- `FIREBASE_SERVICE_ACCOUNT_JSON`: the complete contents of the downloaded service-account JSON file
4. Redeploy the service. The first storefront request seeds `inventory/products` from PostgreSQL if the Firebase path is empty. Admin product changes and completed checkouts keep it synchronized.

Never commit a service-account JSON file or paste its contents into source code. PostgreSQL remains the source of truth for checkout and stock deductions; Firebase mirrors stock for live storefront updates.
# ITE387-ITE314-E-Commerce-System
# ITE387-ITE314-E-Commerce-System
# ITE387-ITE314-E-Commerce-System
