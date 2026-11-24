-- ระบบยืม-คืนหุ่น CPR — รันใน phpMyAdmin หลังเลือกฐานข้อมูล cpr_loan_db
--
-- ถ้าเคยรัน setup.sql เวอร์ชันก่อนหน้าแล้ว ให้รัน 3 บรรทัดนี้ก่อน (ข้อมูลใบยืมเดิมจะหาย / ตารางผู้ใช้ไม่ถูกลบ)
-- DROP TABLE IF EXISTS cpr_return_items;
-- DROP TABLE IF EXISTS cpr_loan_items;
-- DROP TABLE IF EXISTS cpr_loans;

CREATE TABLE IF NOT EXISTS cpr_loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ref_code VARCHAR(8) NOT NULL UNIQUE,            -- รหัสติดตามสถานะ (ผู้ยืมใช้ตรวจสอบ/แจ้งคืน)
  -- ส่วนที่ 1: ขอยืม
  loan_date DATE NOT NULL,
  borrower_name VARCHAR(150) NOT NULL,
  borrower_phone VARCHAR(30) NOT NULL,
  department VARCHAR(150),
  purpose VARCHAR(255),
  date_from DATE NOT NULL,
  date_to DATE NOT NULL,
  total_days INT,
  cpr_qty INT NOT NULL DEFAULT 1,
  due_date DATE,                                  -- กำหนดส่งคืน (ค่าเริ่มต้น = date_to)
  -- สถานะ: 0=รอตรวจสอบ 1=อนุมัติ/กำลังยืม 4=แจ้งคืนแล้ว(รอยืนยัน) 2=คืนแล้ว 3=ไม่อนุมัติ
  status TINYINT NOT NULL DEFAULT 0,
  lender_name VARCHAR(150),                       -- ผู้ให้ยืม (เจ้าหน้าที่)
  approved_at DATETIME NULL,
  reject_reason VARCHAR(255) NULL,
  -- ส่วนที่ 2: ส่งคืน
  return_date DATE NULL,
  returner_name VARCHAR(150) NULL,
  receiver_name VARCHAR(150) NULL,                -- ผู้รับคืน (เจ้าหน้าที่)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (status),
  INDEX (date_from, date_to)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cpr_manikins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  details TEXT NULL,
  is_active TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE cpr_manikins ADD COLUMN IF NOT EXISTS details TEXT NULL AFTER name;

CREATE TABLE IF NOT EXISTS cpr_loan_manikins (
  loan_id INT NOT NULL,
  manikin_id INT NOT NULL,
  manikin_name VARCHAR(100) NOT NULL,
  PRIMARY KEY (loan_id, manikin_id),
  INDEX (manikin_id),
  FOREIGN KEY (loan_id) REFERENCES cpr_loans(id) ON DELETE CASCADE,
  FOREIGN KEY (manikin_id) REFERENCES cpr_manikins(id) ON DELETE RESTRICT
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO cpr_manikins (name) VALUES
  ('Tommy 1'), ('Tommy 2'), ('Tommy 3'), ('Tommy 4'),
  ('Taylor'), ('Davin'), ('Jacob'), ('Edward'), ('Madison'),
  ('Angelina'), ('Kimberly'), ('Jasmine'), ('Alexandra'), ('Sarah'), ('Morgan');

CREATE TABLE IF NOT EXISTS cpr_loan_items (       -- อุปกรณ์ที่ยืมเพิ่มเติม
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  item_name VARCHAR(200) NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  FOREIGN KEY (loan_id) REFERENCES cpr_loans(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cpr_return_items (     -- ตรวจสอบสภาพตอนคืน
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  item_name VARCHAR(200) NOT NULL,
  qty INT NOT NULL DEFAULT 0,
  item_cond TINYINT NOT NULL DEFAULT 1,           -- 1=สมบูรณ์ 2=ชำรุด 3=สูญหาย
  detail VARCHAR(255),
  FOREIGN KEY (loan_id) REFERENCES cpr_loans(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cpr_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',       -- admin | user
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cpr_settings (
  skey VARCHAR(50) PRIMARY KEY,
  svalue VARCHAR(255) NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO cpr_settings (skey, svalue) VALUES ('total_cpr', '5');   -- จำนวนหุ่น CPR ทั้งหมด (แก้ได้ในหน้า "ตั้งค่า")
