CREATE TABLE people (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(20),
    last_name VARCHAR(20),
    phone_number VARCHAR(20)
);

CREATE TABLE couple (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    primary_person_id INT,
    secondary_person_id INT,
    FOREIGN KEY (primary_person_id) REFERENCES people(id),
    FOREIGN KEY (secondary_person_id) REFERENCES people(id)
);

CREATE TABLE product_order (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    couple_id INT,
    start_date DATETIME,
    active TINYINT(1),
    FOREIGN KEY (couple_id) REFERENCES couple(id)
);

CREATE TABLE stripe_data (
    payment_intent_id VARCHAR(128) NOT NULL PRIMARY KEY,
    charge_id VARCHAR(128),
    coupon_id VARCHAR(128)
);

CREATE TABLE product_order_to_stripe_data (
      product_order_id INT,
      stripe_data_payment_intent_id VARCHAR(128),
      FOREIGN KEY (stripe_data_payment_intent_id) REFERENCES stripe_data(payment_intent_id),
      FOREIGN KEY (product_order_id) REFERENCES product_order(id),
      PRIMARY KEY (product_order_id, stripe_data_payment_intent_id)
);


CREATE TABLE daily_question (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(128)
);

CREATE TABLE daily_send_cron_status (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    execution_time DATETIME,
    status ENUM('executing', 'complete')
);

CREATE TABLE send_receipt (
    couple_id INT,
    question_id INT,
    send_time DATETIME,
    twilio_sids JSON,
    PRIMARY KEY (couple_id, question_id),
    FOREIGN KEY (couple_id) REFERENCES couple(id),
    FOREIGN KEY (question_id) REFERENCES daily_question(id)
);
