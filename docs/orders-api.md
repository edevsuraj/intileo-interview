# Orders API Documentation

Base URL:

```text
/api/v1
```

Protected endpoints require a Sanctum bearer token:

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

## Minimal Auth

Use register or login to get the bearer token required for orders APIs.

### Register

```http
POST /api/v1/users/register
```

Request body:

```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123"
}
```

Success response: `201 Created`

```json
{
  "message": "User created successfully.",
  "data": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com",
    "created_at": "2026-06-24T08:00:00.000000Z",
    "updated_at": "2026-06-24T08:00:00.000000Z"
  },
  "token": "plain-text-token",
  "token_type": "Bearer"
}
```

Validation rules:

| Field | Required | Notes |
| --- | --- | --- |
| `name` | Yes | String, max 255 characters |
| `email` | Yes | Valid email, max 255 characters, unique |
| `password` | Yes | Minimum 8 characters |

### Login

```http
POST /api/v1/users/login
```

Request body:

```json
{
  "email": "test@example.com",
  "password": "password123",
  "device_name": "team-api-client"
}
```

`device_name` is optional.

Success response: `200 OK`

```json
{
  "message": "Logged in successfully.",
  "data": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com",
    "created_at": "2026-06-24T08:00:00.000000Z",
    "updated_at": "2026-06-24T08:00:00.000000Z"
  },
  "token": "plain-text-token",
  "token_type": "Bearer"
}
```

Validation rules:

| Field | Required | Notes |
| --- | --- | --- |
| `email` | Yes | Valid email |
| `password` | Yes | String |
| `device_name` | No | String, max 255 characters |

Invalid credentials return a validation error for `email`.

## Orders

All order endpoints require authentication.

### Create Order

Creates one or more order records for the authenticated user and reduces product stock.

```http
POST /api/v1/orders/create
```

Request body:

```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 2,
      "quantity": 12
    }
  ]
}
```

Request fields:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `items` | array | Yes | List of products to order |
| `items[].product_id` | integer | Yes | Must match an existing product |
| `items[].quantity` | integer | Yes | Quantity to order |

Success response: `201 Created`

```json
{
  "success": true,
  "message": "Order created successfully"
}
```

Possible error responses:

`400 Bad Request`

```json
{
  "success": false,
  "message": "Product stock is not enough"
}
```

`500 Internal Server Error`

```json
{
  "success": false,
  "message": "Order creation failed: {error message}"
}
```

Discount rules currently applied:

| Condition | Discount |
| --- | --- |
| Total amount is greater than `5000` | 10% amount discount |
| Quantity is greater than `10` | 5% quantity discount |
| No matching condition | No discount |

If both amount and quantity conditions match, the amount discount is applied first because it is checked first.

Created order fields:

| Field | Notes |
| --- | --- |
| `user_id` | Authenticated user ID |
| `product_id` | Ordered product ID |
| `quantity` | Ordered quantity |
| `status` | Set to `confirmed` on creation |
| `total_amount` | Product amount multiplied by quantity |
| `discount_type` | `amount`, `quantity`, or `null` |
| `disount_condition` | Stored discount reason; note current database field spelling |
| `discount_amount` | Discount amount |
| `discount_percentage` | Discount percentage |
| `final_amount` | Total after discount |

### List Orders

Returns paginated orders for the authenticated user.

```http
GET /api/v1/orders
```

Optional query parameters:

| Parameter | Required | Notes |
| --- | --- | --- |
| `status` | No | Filters by order status, for example `confirmed` or `cancelled` |
| `start_date` | No | Start date for created date range |
| `end_date` | No | End date for created date range |

Example:

```http
GET /api/v1/orders?status=confirmed
```

Example:

```http
GET /api/v1/orders?start_date=2026-06-01&end_date=2026-06-30
```

Success response: `200 OK`

```json
{
  "success": true,
  "message": "Orders retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "product_id": 1,
        "quantity": 2,
        "status": "confirmed",
        "total_amount": "1000.00",
        "discount_type": null,
        "disount_condition": null,
        "discount_amount": "0.00",
        "discount_percentage": "0.00",
        "final_amount": "1000.00",
        "created_at": "2026-06-24T08:00:00.000000Z",
        "updated_at": "2026-06-24T08:00:00.000000Z"
      }
    ],
    "first_page_url": "http://example.test/api/v1/orders?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://example.test/api/v1/orders?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://example.test/api/v1/orders",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

Notes:

- Results are paginated with 10 records per page.
- If `status` is provided, date range filtering is not applied.
- Date range filtering only applies when both `start_date` and `end_date` are provided.

Possible error response: `500 Internal Server Error`

```json
{
  "success": false,
  "message": "Something went wrong",
  "error": "{error message}"
}
```

### Order Report

Returns a summary report across orders.

```http
GET /api/v1/orders/report
```

Optional query parameters:

| Parameter | Required | Notes |
| --- | --- | --- |
| `start_date` | No | Start date for created date range |
| `end_date` | No | End date for created date range |

Example:

```http
GET /api/v1/orders/report?start_date=2026-06-01&end_date=2026-06-30
```

Success response: `200 OK`

```json
{
  "success": true,
  "message": "Report generated successfully",
  "data": {
    "total_orders": 25,
    "total_revenue": "12000.00",
    "total_discounts": "800.00",
    "most_ordered_product": {
      "product_id": 1,
      "product_name": "Sample Product",
      "total_count": 8
    }
  }
}
```

If there are no matching orders, `most_ordered_product` can be `null`.

Possible error response: `500 Internal Server Error`

```json
{
  "success": false,
  "message": "Something went wrong",
  "error": "{error message}"
}
```

### Cancel Order

Cancels an order owned by the authenticated user and restores the ordered product quantity back to stock.

```http
PATCH /api/v1/orders/{orderId}/cancel
```

Path parameters:

| Parameter | Required | Notes |
| --- | --- | --- |
| `orderId` | Yes | Order ID to cancel |

Success response: `200 OK`

```json
{
  "success": true,
  "message": "Order cancelled successfully"
}
```

Possible error responses:

`403 Forbidden`

```json
{
  "success": false,
  "message": "You are not authorized to cancel this order"
}
```

`400 Bad Request`

```json
{
  "success": false,
  "message": "Order is already cancelled"
}
```

`500 Internal Server Error`

```json
{
  "success": false,
  "message": "Something went wrong",
  "error": "{error message}"
}
```

## Common Unauthorized Response

Orders APIs return this response when the bearer token is missing or invalid:

```json
{
  "message": "Unauthenticated."
}
```

