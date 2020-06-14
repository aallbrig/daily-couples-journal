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
    purchase_date DATETIME,
    stripe_client_secret VARCHAR(64),
    couple_id INT,
    FOREIGN KEY (couple_id) REFERENCES couple(id)
);

CREATE TABLE daily_question (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(128)
);