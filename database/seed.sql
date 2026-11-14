USE brew_and_bloom_db;

INSERT INTO `categories` (`id`, `name`, `sort_order`) VALUES
(1, 'Signature Milk Tea', 1),
(2, 'Fruit Tea', 2),
(3, 'Iced Coffee', 3),
(4, 'Waffles & Snacks', 4);

INSERT INTO `products` (`category_id`, `name`, `description`, `price`) VALUES
(1, 'Bloom Brown Sugar Milk Tea', 'Black tea with fresh milk, brown sugar syrup and chewy pearls.', 125.00),
(1, 'Classic Wintermelon Milk Tea', 'Smooth wintermelon-infused milk tea served over ice.', 110.00),
(2, 'Passionfruit Green Tea', 'Refreshing green tea with real passionfruit pulp.', 115.00),
(3, 'Spanish Latte Iced', 'Espresso with sweetened condensed milk over ice.', 135.00),
(3, 'Caramel Cold Brew', 'Slow-steeped cold brew with house caramel drizzle.', 140.00),
(4, 'Honey Butter Waffle', 'Crisp golden waffle with whipped honey butter.', 95.00);

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('store_name', 'Brew & Bloom'),
('is_accepting_orders', '1'),
('pickup_instructions', 'Please show your order code at the counter when picking up your order.'),
('delivery_note', 'Delivery is available within the local area. Fees and estimated time will be confirmed by the shop.');
