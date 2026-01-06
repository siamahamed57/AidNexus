/* =========================
   SEQUENCES FOR AUTO-INCREMENT
========================= */

CREATE SEQUENCE victim_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE doctor_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE users_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE roles_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE case_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE appointment_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE consent_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE note_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE medical_rec_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE legal_doc_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE financial_aid_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE user_role_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE notification_seq START WITH 1 INCREMENT BY 1;

/* =========================
   PARENT TABLES
========================= */

CREATE TABLE victim (
    victim_id NUMBER PRIMARY KEY,
    victim_name VARCHAR2(100) NOT NULL,
    dob DATE NOT NULL,
    gender VARCHAR2(10) NOT NULL,
    contact_info VARCHAR2(100) NOT NULL,
    address VARCHAR2(200) NOT NULL
);

CREATE TABLE doctor (
    doctor_id NUMBER PRIMARY KEY,
    doctor_name VARCHAR2(100) NOT NULL,
    specialty VARCHAR2(50),
    contact_info VARCHAR2(100)
);

CREATE TABLE users (
    user_id NUMBER PRIMARY KEY,
    username VARCHAR2(50) UNIQUE NOT NULL,
    password_hash VARCHAR2(200) NOT NULL,
    email VARCHAR2(100) UNIQUE
);

CREATE TABLE roles (
    role_id NUMBER PRIMARY KEY,
    role_name VARCHAR2(30) UNIQUE NOT NULL
);

/* =========================
   CHILD / RELATION TABLES
========================= */

CREATE TABLE case_t (
    case_id NUMBER PRIMARY KEY,
    case_type VARCHAR2(50) NOT NULL,
    case_status VARCHAR2(20) NOT NULL,
    open_date DATE DEFAULT SYSDATE,
    close_date DATE,
    victim_id NUMBER NOT NULL,
    CONSTRAINT fk_case_victim
        FOREIGN KEY (victim_id) REFERENCES victim(victim_id)
);

CREATE TABLE appointment (
    appointment_id NUMBER PRIMARY KEY,
    appt_datetime DATE NOT NULL,
    location VARCHAR2(100),
    status VARCHAR2(20),
    case_id NUMBER,
    doctor_id NUMBER,
    CONSTRAINT fk_appt_case
        FOREIGN KEY (case_id) REFERENCES case_t(case_id),
    CONSTRAINT fk_appt_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctor(doctor_id)
);

CREATE TABLE consent (
    consent_id NUMBER PRIMARY KEY,
    consent_type VARCHAR2(50) NOT NULL,
    signed_date DATE DEFAULT SYSDATE,
    version VARCHAR2(10),
    victim_id NUMBER NOT NULL,
    CONSTRAINT fk_consent_victim
        FOREIGN KEY (victim_id) REFERENCES victim(victim_id)
);

CREATE TABLE case_note (
    note_id NUMBER PRIMARY KEY,
    note_date DATE DEFAULT SYSDATE,
    author_id NUMBER NOT NULL,
    note_text VARCHAR2(4000),
    case_id NUMBER NOT NULL,
    CONSTRAINT fk_note_case
        FOREIGN KEY (case_id) REFERENCES case_t(case_id)
);

CREATE TABLE medical_rec (
    record_id NUMBER PRIMARY KEY,
    diagnosis VARCHAR2(200),
    treatment_plan VARCHAR2(2000),
    record_date DATE DEFAULT SYSDATE,
    case_id NUMBER NOT NULL,
    CONSTRAINT fk_med_case
        FOREIGN KEY (case_id) REFERENCES case_t(case_id)
);

CREATE TABLE legal_doc (
    document_id NUMBER PRIMARY KEY,
    doc_type VARCHAR2(50),
    issue_date DATE DEFAULT SYSDATE,
    file_path VARCHAR2(200),
    case_id NUMBER NOT NULL,
    CONSTRAINT fk_legal_case
        FOREIGN KEY (case_id) REFERENCES case_t(case_id)
);

CREATE TABLE financial_aid (
    aid_id NUMBER PRIMARY KEY,
    amount NUMBER(12,2),
    aid_type VARCHAR2(50),
    disburse_date DATE DEFAULT SYSDATE,
    case_id NUMBER NOT NULL,
    CONSTRAINT fk_aid_case
        FOREIGN KEY (case_id) REFERENCES case_t(case_id)
);

CREATE TABLE user_role (
    user_role_id NUMBER PRIMARY KEY,
    user_id NUMBER NOT NULL,
    role_id NUMBER NOT NULL,
    CONSTRAINT fk_ur_user
        FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_ur_role
        FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE notification (
    notification_id NUMBER PRIMARY KEY,
    channel VARCHAR2(10),
    sent_datetime DATE DEFAULT SYSDATE,
    status VARCHAR2(20),
    case_id NUMBER,
    sender_id NUMBER,
    CONSTRAINT fk_notif_case
        FOREIGN KEY (case_id) REFERENCES case_t(case_id),
    CONSTRAINT fk_notif_user
        FOREIGN KEY (sender_id) REFERENCES users(user_id)
);
