WITH next_payment_policies AS (
    SELECT DISTINCT
        pocy.pocy_no,
        pocy.eff_date,
        TO_CHAR(pocy.eff_date, 'YYYY') || '-' || (TO_CHAR(pocy.eff_date, 'YYYY') + 1) pol_year,
        FN_GET_SYS_CODE_DESC(pocy.scma_oid_payment_mode, 'en') pay_mode,
        'Next Payment' rn_np_status,
        poho.poho_name_1 || ' ' || poho.poho_name_2 poho_name,
        brkr.brkr_no || ' - ' || TRIM(BOTH ' ' FROM brkr.brkr_name_1 || ' ' || brkr.brkr_name_2) brkr,
        frln.brkr_no || ' - ' || TRIM(BOTH ' ' FROM frln.brkr_name_1 || ' ' || frln.brkr_name_2) frln,
        COUNT(DISTINCT memb.mbr_no) memb_count
    FROM mr_member memb
        INNER JOIN mr_member_plan mepl
                ON memb.memb_oid = mepl.memb_oid
        INNER JOIN mr_policy_plan popl
                ON mepl.popl_oid = popl.popl_oid
        INNER JOIN mr_policy pocy
                ON popl.pocy_oid = pocy.pocy_oid
        INNER JOIN mr_policyholder poho
                ON pocy.poho_oid = poho.poho_oid
        INNER JOIN pd_plan plan
                ON popl.plan_oid = plan.plan_oid
        INNER JOIN rt_product_type prty
                ON plan.prty_oid = prty.prty_oid
        INNER JOIN bf_debit_note note
                ON note.pocy_oid = pocy.pocy_oid
        INNER JOIN cm_broker brkr
                ON pocy.brkr_oid_pri_corr = brkr.brkr_oid
         LEFT JOIN cm_broker frln
                ON pocy.brkr_oid_front_liner = frln.brkr_oid
    WHERE TO_CHAR(ADD_MONTHS(pocy.eff_date, 6), 'YYYYMM') = TO_CHAR(ADD_MONTHS(CURRENT_DATE, 1), 'YYYYMM')
      AND pocy.term_date IS NULL
      AND pocy.proforma_ind = 'N'
      AND pocy.scma_oid_payment_mode = 'PAYMENT_MODE_S'
      AND prty.scma_oid_product = 'PRODUCT_MD'
    GROUP BY
        pocy.pocy_no,
        pocy.eff_date,
        pocy.scma_oid_payment_mode,
        pocy.crt_date,
        poho.poho_name_1,
        poho.poho_name_2,
        brkr.brkr_no,
        brkr.brkr_name_1,
        brkr.brkr_name_2,
        frln.brkr_no,
        frln.brkr_name_1,
        frln.brkr_name_2
),
proforma_policies AS (
    SELECT DISTINCT
        pocy.pocy_no,
        ADD_MONTHS(pocy.eff_date, 12) eff_date,
        TO_CHAR(ADD_MONTHS(pocy.eff_date, 12), 'YYYY') || '-' || (TO_CHAR(ADD_MONTHS(pocy.eff_date, 12), 'YYYY') + 1) pol_year,
        FN_GET_SYS_CODE_DESC(pocy.scma_oid_payment_mode, 'en') pay_mode,
        'Renew' rn_np_status,
        poho.poho_name_1 || ' ' || poho.poho_name_2 poho_name,
        brkr.brkr_no || ' - ' || TRIM(BOTH ' ' FROM brkr.brkr_name_1 || ' ' || brkr.brkr_name_2) brkr,
        frln.brkr_no || ' - ' || TRIM(BOTH ' ' FROM frln.brkr_name_1 || ' ' || frln.brkr_name_2) frln,
        COUNT(DISTINCT memb.mbr_no) memb_count
    FROM mr_member memb
        INNER JOIN mr_member_plan mepl
                ON memb.memb_oid = mepl.memb_oid
        INNER JOIN mr_policy_plan popl
                ON mepl.popl_oid = popl.popl_oid
        INNER JOIN mr_policy pocy
                ON popl.pocy_oid = pocy.pocy_oid
        INNER JOIN mr_policyholder poho
                ON pocy.poho_oid = poho.poho_oid
        INNER JOIN pd_plan plan
                ON popl.plan_oid = plan.plan_oid
        INNER JOIN rt_product_type prty
                ON plan.prty_oid = prty.prty_oid
        INNER JOIN bf_debit_note note
                ON note.pocy_oid = pocy.pocy_oid
        INNER JOIN cm_broker brkr
                ON pocy.brkr_oid_pri_corr = brkr.brkr_oid
         LEFT JOIN cm_broker frln
                ON pocy.brkr_oid_front_liner = frln.brkr_oid
    WHERE TO_CHAR(pocy.exp_date + 1, 'YYYYMM') = TO_CHAR(ADD_MONTHS(CURRENT_DATE, 2), 'YYYYMM')
      AND pocy.term_date IS NULL
      AND pocy.proforma_ind = 'N'
      AND prty.scma_oid_product = 'PRODUCT_MD'
    GROUP BY
        pocy.pocy_no,
        pocy.eff_date,
        pocy.scma_oid_payment_mode,
        pocy.crt_date,
        poho.poho_name_1,
        poho.poho_name_2,
        brkr.brkr_no,
        brkr.brkr_name_1,
        brkr.brkr_name_2,
        frln.brkr_no,
        frln.brkr_name_1,
        frln.brkr_name_2
)
SELECT DISTINCT
    TO_CHAR(pocy.eff_date, 'YYYY-MM-DD') "pol_eff_date",
    (
        REPLACE(pocy.pocy_no, SUBSTR(pocy.pocy_no, -8), '') || '-' ||
        SUBSTR(pocy.pocy_no, -8, 3) || '-' ||
        SUBSTR(pocy.pocy_no, -5)
    ) "pol_no",
    UPPER(pocy.poho_name) "poho_name",
    DECODE(pocy.brkr, ' - ', NULL, pocy.brkr) "brkr",
    DECODE(pocy.frln, ' - ', NULL, pocy.frln) "frln",
    pocy.pol_year "pol_year",
    pocy.pay_mode "pay_mode",
    pocy.rn_np_status "rn_np_status",
    pocy.memb_count "memb_count"
FROM proforma_policies pocy
UNION ALL
SELECT DISTINCT
    TO_CHAR(pocy.eff_date, 'YYYY-MM-DD') "pol_eff_date",
    (
        REPLACE(pocy.pocy_no, SUBSTR(pocy.pocy_no, -8), '') || '-' ||
        SUBSTR(pocy.pocy_no, -8, 3) || '-' ||
        SUBSTR(pocy.pocy_no, -5)
    ) "pol_no",
    UPPER(pocy.poho_name) "poho_name",
    DECODE(pocy.brkr, ' - ', NULL, pocy.brkr) "brkr",
    DECODE(pocy.frln, ' - ', NULL, pocy.frln) "frln",
    pocy.pol_year "pol_year",
    pocy.pay_mode "pay_mode",
    pocy.rn_np_status "rn_np_status",
    pocy.memb_count "memb_count"
FROM next_payment_policies pocy