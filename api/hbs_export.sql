WITH fn_memb_pocy AS
(
    SELECT DISTINCT
        memb.memb_oid,
        MAX(pocy.eff_date) pocy_eff_date
    FROM mr_member memb
        JOIN mr_member_plan mepl
          ON memb.memb_oid = mepl.memb_oid
        JOIN mr_policy_plan popl
          ON mepl.popl_oid = popl.popl_oid
        JOIN mr_policy pocy
          ON popl.pocy_oid = pocy.pocy_oid
        JOIN pd_plan plan
          ON popl.plan_oid = plan.plan_oid
        JOIN rt_product_type prty
          ON plan.prty_oid = prty.prty_oid
    WHERE prty.scma_oid_product = 'PRODUCT_MD'
      AND pocy.proforma_ind = 'N'
    GROUP BY memb.memb_oid
)
SELECT
    pocy.pocy_no "pol_no",
    poho.poho_name_1 "poho_name",
    memb.mbr_no "mbr_no",
    INITCAP(memb.mbr_last_name || ' ' || memb.mbr_first_name) "mbr_name",
    TO_CHAR(memb.dob, 'DD/MM/YYYY') "dob",
    memb.id_card_no "id_card",
    'Recruited' "src",
    LPAD(brkr.brkr_no, 5, 0) || ' - ' || brkr.brkr_name_1 "brkr",
    TO_CHAR(MAX(rcpy.receive_date), 'DD/MM/YYYY') "paid_date",
    REGEXP_REPLACE(ASCIISTR(meev.event_desc), '\\[[:xdigit:]]{4}', '') "fin_offer",
    LISTAGG(REGEXP_REPLACE(ASCIISTR(popl.pocy_plan_desc), '\\[[:xdigit:]]{4}', ''), '; ') WITHIN GROUP (ORDER BY popl.crt_date) "fin_plan"
FROM fn_memb_pocy fmpc
    INNER JOIN mr_member memb
            ON memb.memb_oid = fmpc.memb_oid
    INNER JOIN mr_member_plan mepl
            ON memb.memb_oid = mepl.memb_oid
    INNER JOIN mr_policy_plan popl
            ON mepl.popl_oid = popl.popl_oid
    INNER JOIN mr_policy pocy
            ON popl.pocy_oid = pocy.pocy_oid
           AND fmpc.pocy_eff_date = pocy.eff_date
    INNER JOIN mr_policyholder poho
            ON poho.poho_oid = pocy.poho_oid
    INNER JOIN bf_received_payment rcpy
            ON rcpy.pocy_oid = pocy.pocy_oid
    INNER JOIN cm_broker brkr
            ON pocy.brkr_oid_pri_corr = brkr.brkr_oid
     LEFT JOIN mr_member_event meev
            ON meev.memb_oid = memb.memb_oid
WHERE mepl.status IS NULL
  AND pocy.proforma_ind = 'N'
GROUP BY
    pocy.pocy_no,
    poho.poho_name_1,
    memb.mbr_no,
    memb.mbr_last_name,
    memb.mbr_first_name,
    memb.dob,
    memb.id_card_no,
    brkr.brkr_no,
    brkr.brkr_name_1,
    meev.event_desc
ORDER BY memb.mbr_no
;