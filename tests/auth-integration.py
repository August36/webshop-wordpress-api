import json
import secrets
import subprocess
import unittest
import urllib.error
import urllib.request


BASE_URL = "http://localhost:8080/wp-json"


def wordpress_php(source):
    result = subprocess.run(
        ["docker", "exec", "-i", "webshop-wordpress", "php"],
        input="<?php\nrequire '/var/www/html/wp-load.php';\n" + source,
        text=True,
        capture_output=True,
        check=True,
    )
    return result.stdout.strip()


def request(path, payload=None, token=None):
    headers = {}
    data = None
    if payload is not None:
        headers["Content-Type"] = "application/json"
        data = json.dumps(payload).encode()
    if token:
        headers["Authorization"] = "Bearer " + token
    req = urllib.request.Request(BASE_URL + path, data=data, headers=headers)
    try:
        response = urllib.request.urlopen(req, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        body = response.read()
        return response.status, json.loads(body) if body else None


class AuthenticationIntegrationTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        suffix = secrets.token_hex(12)
        cls.username = "auth_test_" + suffix
        cls.email = "auth_test_" + suffix + "@example.com"
        cls.password = secrets.token_hex(24)
        cls.user_id = int(wordpress_php(
            "$id = wp_insert_user(["
            f"'user_login' => '{cls.username}', "
            f"'user_email' => '{cls.email}', "
            f"'user_pass' => '{cls.password}', "
            "'role' => 'subscriber']);"
            "if (is_wp_error($id)) { fwrite(STDERR, $id->get_error_message()); exit(1); }"
            "echo $id;"
        ))
        cls.addClassCleanup(cls.delete_test_user)
        cls.product_id = int(wordpress_php(
            "$id = wp_insert_post(["
            "'post_type' => 'product', "
            f"'post_title' => '{cls.username}', "
            "'post_status' => 'publish'], true);"
            "if (is_wp_error($id)) { fwrite(STDERR, $id->get_error_message()); exit(1); }"
            "update_post_meta($id, 'price', '12.50');"
            "echo $id;"
        ))
        cls.addClassCleanup(cls.delete_test_product)

    @classmethod
    def delete_test_user(cls):
        wordpress_php(
            "require_once ABSPATH . 'wp-admin/includes/user.php';"
            f"$user = get_userdata({cls.user_id});"
            f"if (!$user || $user->user_login !== '{cls.username}') {{ exit(1); }}"
            f"$orders = get_posts(['post_type' => 'shop_order', 'post_status' => 'any', "
            f"'numberposts' => -1, 'meta_key' => 'user_id', 'meta_value' => {cls.user_id}]);"
            "foreach ($orders as $order) { if (!wp_delete_post($order->ID, true)) { exit(1); } }"
            f"if (!wp_delete_user({cls.user_id})) {{ exit(1); }}"
        )

    @classmethod
    def delete_test_product(cls):
        wordpress_php(
            f"$post = get_post({cls.product_id});"
            f"if (!$post || $post->post_type !== 'product' || $post->post_title !== '{cls.username}') {{ exit(1); }}"
            f"if (!wp_delete_post({cls.product_id}, true)) {{ exit(1); }}"
        )

    def login(self):
        status, body = request("/api/login", {
            "email": self.email,
            "password": self.password,
        })
        self.assertEqual(status, 200, body)
        self.assertEqual(body["user"]["userID"], self.user_id)
        return body["token"]

    def test_login_authentication_and_logout(self):
        status, _ = request("/api/login", {"email": self.email, "password": "wrong"})
        self.assertEqual(status, 401)
        status, _ = request("/api/login", {"email": self.username, "password": self.password})
        self.assertEqual(status, 400)
        status, _ = request("/jwt-auth/v1/token", {
            "username": self.username,
            "password": self.password,
        })
        self.assertEqual(status, 404)

        first_token = self.login()
        second_token = self.login()
        status, _ = request(f"/api/orders/{self.user_id}", token=first_token)
        self.assertEqual(status, 200)
        status, _ = request(f"/api/orders/{self.user_id + 1}", token=first_token)
        self.assertEqual(status, 403)
        status, _ = request("/api/products", {"name": "not allowed"}, token=first_token)
        self.assertEqual(status, 403)

        status, body = request(f"/api/orders/{self.user_id}", {
            "product_id": self.product_id,
            "quantity": 2,
        }, token=first_token)
        self.assertEqual(status, 201, body)
        order_id = body["order"]["order_id"]
        status, _ = request(f"/api/orders/{order_id}/items", {
            "product_id": self.product_id,
            "quantity": 3,
        }, token=first_token)
        self.assertIn(status, (200, 201))
        status, body = request(f"/api/orders/{self.user_id}", token=first_token)
        self.assertEqual(status, 200, body)
        self.assertTrue(any(order["order_id"] == order_id for order in body["orderItems"]))

        status, _ = request("/api/logout", {}, token=first_token)
        self.assertEqual(status, 200)
        status, _ = request(f"/api/orders/{self.user_id}", token=first_token)
        self.assertIn(status, (401, 403))
        status, _ = request("/jwt-auth/v1/token/validate", {}, token=first_token)
        self.assertIn(status, (401, 403))
        status, _ = request(f"/api/orders/{self.user_id}", token=second_token)
        self.assertEqual(status, 200)
        status, _ = request("/api/logout", {}, token=second_token)
        self.assertEqual(status, 200)


if __name__ == "__main__":
    unittest.main()
