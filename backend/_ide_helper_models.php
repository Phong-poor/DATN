<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $ten
 * @property string $email
 * @property string|null $sodienthoai
 * @property string|null $gioitinh
 * @property string|null $ngaysinh
 * @property string $matkhau
 * @property string $vaitro
 * @property string|null $anhdaidien
 * @property string $trangthai
 * @property \Carbon\CarbonImmutable|null $hoat_dong_cuoi_luc
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property \Carbon\CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $otp_khoiphuc
 * @property string|null $otp_khoiphuc_hethan_luc
 * @property string|null $id_google
 * @property string|null $id_facebook
 * @property string|null $so_cccd
 * @property string|null $ngay_cap_cccd
 * @property string|null $noi_cap_cccd
 * @property string|null $anh_cccd_mat_truoc
 * @property string|null $anh_cccd_mat_sau
 * @property string|null $remember_token
 * @property string|null $face_descriptor
 * @property int $face_registered
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChamCong> $chamCongs
 * @property-read int|null $cham_congs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DatHang> $donHangs
 * @property-read int|null $don_hangs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DonXinNghi> $donXinNghis
 * @property-read int|null $don_xin_nghis_count
 * @property string|null $avatar
 * @property-read mixed $cac_quyen
 * @property mixed $date_of_birth
 * @property string|null $gender
 * @property mixed $last_active_at
 * @property string|null $name
 * @property-read bool $online
 * @property string|null $password
 * @property string|null $phone
 * @property string|null $role
 * @property string|null $status
 * @property-read mixed $ten_vaitro_hienthi
 * @property-read \App\Models\LichLamNhanVien|null $lichLamNhanVien
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereAnhCccdMatSau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereAnhCccdMatTruoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereAnhdaidien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereFaceDescriptor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereFaceRegistered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereGioitinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereHoatDongCuoiLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereIdFacebook($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereIdGoogle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereMatkhau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereNgayCapCccd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereNgaysinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereNoiCapCccd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereOtpKhoiphuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereOtpKhoiphucHethanLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereSoCccd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereSodienthoai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereTen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereVaitro($value)
 */
	class Admin extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_khachhang
 * @property string|null $hanhdong
 * @property string|null $tenmodel
 * @property string|null $id_doituong
 * @property string|null $mota
 * @property string|null $diachi_ip
 * @property string|null $user_agent
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read mixed $action
 * @property-read mixed $description
 * @property-read mixed $ip_address
 * @property-read mixed $model_name
 * @property-read \App\Models\Admin $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereDiachiIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereHanhdong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereIdDoituong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereMota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereTenmodel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminActivityLog whereUserAgent($value)
 */
	class AdminActivityLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_affiliate_khachhang
 * @property int|null $id_khachhang_duoc_gioithieu
 * @property int|null $id_donhang
 * @property numeric $so_tien
 * @property string $trangthai
 * @property \Carbon\CarbonImmutable|null $duoc_duyet_luc
 * @property \Carbon\CarbonImmutable|null $duoc_thanh_toan_luc
 * @property string|null $ghichu
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $affiliateUser
 * @property-read mixed $affiliate_user_id
 * @property-read mixed $amount
 * @property mixed $approved_at
 * @property mixed $note
 * @property-read mixed $order_id
 * @property mixed $paid_at
 * @property-read mixed $referred_user_id
 * @property mixed $status
 * @property-read \App\Models\DatHang|null $order
 * @property-read \App\Models\User|null $referredUser
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereDuocDuyetLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereDuocThanhToanLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereGhichu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereIdAffiliateKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereIdDonhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereIdKhachhangDuocGioithieu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereSoTien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateCommission whereUpdatedAt($value)
 */
	class AffiliateCommission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_khachhang
 * @property string $ma_affiliate
 * @property numeric $ty_le_hoa_hong
 * @property string $trangthai
 * @property numeric $tong_thu_nhap
 * @property numeric $tong_da_thanh_toan
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read mixed $affiliate_code
 * @property mixed $commission_rate
 * @property mixed $status
 * @property-read mixed $total_earned
 * @property-read mixed $total_paid
 * @property-read mixed $user_id
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile whereMaAffiliate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile whereTongDaThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile whereTongThuNhap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile whereTyLeHoaHong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateProfile whereUpdatedAt($value)
 */
	class AffiliateProfile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_affiliate_khachhang
 * @property int $id_khachhang_duoc_gioithieu
 * @property string|null $ma_ref
 * @property \Carbon\CarbonImmutable|null $da_dang_ky_luc
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $affiliateUser
 * @property-read \App\Models\User $referredUser
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral whereDaDangKyLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral whereIdAffiliateKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral whereIdKhachhangDuocGioithieu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral whereMaRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateReferral whereUpdatedAt($value)
 */
	class AffiliateReferral extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_affiliate_khachhang
 * @property int|null $id_sanpham
 * @property string $tieu_de
 * @property string|null $mo_ta
 * @property string|null $video_path
 * @property string|null $video_url
 * @property string|null $thumbnail_path
 * @property string $trangthai
 * @property bool $noi_bat
 * @property int $luot_xem
 * @property int $luot_click
 * @property string|null $ly_do_tu_choi
 * @property \Carbon\CarbonImmutable|null $duoc_duyet_luc
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $affiliateUser
 * @property-read mixed $affiliate_user_id
 * @property-read mixed $approved_at
 * @property-read mixed $clicks
 * @property-read mixed $description
 * @property-read mixed $featured
 * @property-read mixed $product_id
 * @property-read mixed $product_ids
 * @property-read mixed $products
 * @property-read mixed $reject_reason
 * @property mixed $status
 * @property-read mixed $thumbnail_src
 * @property-read mixed $title
 * @property-read mixed $video_src
 * @property-read mixed $views
 * @property-read \App\Models\SanPham|null $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereDuocDuyetLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereIdAffiliateKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereIdSanpham($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereLuotClick($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereLuotXem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereLyDoTuChoi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereMoTa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereNoiBat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereThumbnailPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereTieuDe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereVideoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateVideo whereVideoUrl($value)
 */
	class AffiliateVideo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property numeric $balance
 * @property numeric $pending_balance
 * @property numeric $total_withdrawn
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet wherePendingBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet whereTotalWithdrawn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWallet whereUserId($value)
 */
	class AffiliateWallet extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $ma_yeu_cau
 * @property int $id_affiliate_khachhang
 * @property numeric $so_tien
 * @property string|null $ten_ngan_hang
 * @property string|null $ten_chu_tai_khoan
 * @property string|null $so_tai_khoan
 * @property string|null $so_dien_thoai_nhan_sms
 * @property string $trangthai
 * @property string|null $nha_cung_cap
 * @property string|null $ma_giao_dich
 * @property array<array-key, mixed>|null $du_lieu_chi_tra
 * @property \Carbon\CarbonImmutable|null $bat_dau_xu_ly_luc
 * @property string|null $ghichu
 * @property \Carbon\CarbonImmutable|null $duoc_duyet_luc
 * @property \Carbon\CarbonImmutable|null $duoc_thanh_toan_luc
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $affiliateUser
 * @property-read mixed $affiliate_user_id
 * @property-read mixed $amount
 * @property mixed $approved_at
 * @property-read mixed $bank_account_name
 * @property-read mixed $bank_account_number
 * @property-read mixed $bank_name
 * @property mixed $note
 * @property mixed $paid_at
 * @property-read mixed $payout_provider
 * @property-read mixed $processing_at
 * @property-read mixed $request_code
 * @property-read mixed $sms_phone
 * @property mixed $status
 * @property-read mixed $transaction_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereBatDauXuLyLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereDuLieuChiTra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereDuocDuyetLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereDuocThanhToanLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereGhichu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereIdAffiliateKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereMaGiaoDich($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereMaYeuCau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereNhaCungCap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereSoDienThoaiNhanSms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereSoTaiKhoan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereSoTien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereTenChuTaiKhoan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereTenNganHang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawRequest whereUpdatedAt($value)
 */
	class AffiliateWithdrawRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property numeric $amount
 * @property string $bank_name
 * @property string $phone_account
 * @property string $account_name
 * @property string $transaction_code
 * @property string|null $idempotency_key
 * @property string $status
 * @property string $sms_status
 * @property string|null $sms_message_id
 * @property string|null $sms_error
 * @property numeric $balance_before
 * @property numeric $balance_after
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read string $masked_phone_account
 * @property-read string $sms_status_label
 * @property-read string $status_label
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereBalanceAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereBalanceBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereIdempotencyKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal wherePhoneAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereSmsError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereSmsMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereSmsStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereTransactionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AffiliateWithdrawal whereUserId($value)
 */
	class AffiliateWithdrawal extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $tieude
 * @property string|null $phude
 * @property string|null $chudenho
 * @property string|null $noibat
 * @property string|null $mota
 * @property string $hinhanh
 * @property string $loaimedia
 * @property string|null $hinhanh_mobile
 * @property string|null $loai_media_mobile
 * @property string|null $duongdan
 * @property int|null $id_sanpham
 * @property string|null $nhanchinh
 * @property string|null $nhanphu
 * @property string|null $huyhieu_sanpham
 * @property string|null $dactinh_sanpham
 * @property int $vitri
 * @property bool $trangthai
 * @property \Carbon\CarbonImmutable|null $batdauluc
 * @property \Carbon\CarbonImmutable|null $ketthucluc
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereBatdauluc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereChudenho($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereDactinhSanpham($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereDuongdan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereHinhanh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereHinhanhMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereHuyhieuSanpham($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereIdSanpham($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereKetthucluc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereLoaiMediaMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereLoaimedia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereMota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereNhanchinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereNhanphu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereNoibat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner wherePhude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereTieude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereVitri($value)
 */
	class Banner extends \Eloquent {}
}

namespace App\Models{
/**
 * Đại diện một phiên bản sản phẩm theo cấu hình thuộc tính, giá và tồn kho.
 *
 * @property int $id_bienthe
 * @property int $id_sanpham
 * @property string|null $ten_bienthe
 * @property numeric $gia
 * @property int $soluong
 * @property string|null $thuoc_tinh_json
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Combo> $comboOffers
 * @property-read int|null $combo_offers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GiaTriThuocTinh> $giaTriThuocTinhs
 * @property-read int|null $gia_tri_thuoc_tinhs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BienTheHinhAnh> $hinhAnhs
 * @property-read int|null $hinh_anhs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DanhGia> $reviews
 * @property-read int|null $reviews_count
 * @property-read \App\Models\SanPham $sanPham
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienThe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienThe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienThe query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienThe whereGia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienThe whereIdBienthe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienThe whereIdSanpham($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienThe whereSoluong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienThe whereTenBienthe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienThe whereThuocTinhJson($value)
 */
	class BienThe extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_bienthe_hinhanh
 * @property int|null $id_sanpham
 * @property string $duongdan
 * @property int $thutu
 * @property-read \App\Models\SanPham|null $sanPham
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienTheHinhAnh newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienTheHinhAnh newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienTheHinhAnh query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienTheHinhAnh whereDuongdan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienTheHinhAnh whereIdBientheHinhanh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienTheHinhAnh whereIdSanpham($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BienTheHinhAnh whereThutu($value)
 */
	class BienTheHinhAnh extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_khachhang
 * @property int|null $id_voucher
 * @property int|null $id_khachhang_voucher
 * @property string $mavoucher
 * @property string $email
 * @property \Carbon\CarbonImmutable $ngaysinh
 * @property \Carbon\CarbonImmutable|null $guiluc
 * @property string $trangthai
 * @property string|null $thongbaoloi
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Promotion|null $promotion
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\UserVoucher|null $userVoucher
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereGuiluc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereIdKhachhangVoucher($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereIdVoucher($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereMavoucher($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereNgaysinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereThongbaoloi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponLog whereUpdatedAt($value)
 */
	class BirthdayCouponLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property bool $kichhoat
 * @property string $giochay
 * @property int $thoi_han_ngay
 * @property int|null $id_voucher
 * @property string $mavoucher
 * @property string $id_mau_email
 * @property bool $gui_mot_lan_moi_nam
 * @property bool $thu_lai_khi_that_bai
 * @property bool $thongbao_admin
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereGiochay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereGuiMotLanMoiNam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereIdMauEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereIdVoucher($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereKichhoat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereMavoucher($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereThoiHanNgay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereThongbaoAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereThuLaiKhiThatBai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BirthdayCouponSetting whereUpdatedAt($value)
 */
	class BirthdayCouponSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ca_sang_bat_dau
 * @property string $ca_sang_ket_thuc
 * @property string $ca_chieu_bat_dau
 * @property string $ca_chieu_ket_thuc
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam whereCaChieuBatDau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam whereCaChieuKetThuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam whereCaSangBatDau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam whereCaSangKetThuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhCaLam whereUpdatedAt($value)
 */
	class CauHinhCaLam extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $thu_tu
 * @property string $ten_ngay
 * @property int $so_xu_thuong
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhDiemDanh newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhDiemDanh newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhDiemDanh query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhDiemDanh whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhDiemDanh whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhDiemDanh whereSoXuThuong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhDiemDanh whereTenNgay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhDiemDanh whereThuTu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhDiemDanh whereUpdatedAt($value)
 */
	class CauHinhDiemDanh extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ti_le_quy_doi
 * @property float $ti_le_tich_luy
 * @property int $phan_tram_giam_toi_da
 * @property bool $trang_thai
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu wherePhanTramGiamToiDa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu whereTiLeQuyDoi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu whereTiLeTichLuy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu whereTrangThai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CauHinhXu whereUpdatedAt($value)
 */
	class CauHinhXu extends \Eloquent {}
}

namespace App\Models{
/**
 * Lưu giờ vào, giờ ra, công làm việc và ảnh minh chứng của nhân viên theo ngày.
 *
 * @property int $id
 * @property int $id_nhanvien
 * @property \Carbon\CarbonImmutable $ngay_cham_cong
 * @property string|null $gio_vao
 * @property string|null $anh_vao
 * @property string|null $gio_ra
 * @property string|null $anh_ra
 * @property int $di_tre_phut
 * @property numeric $tong_gio
 * @property numeric $tong_cong
 * @property string $trang_thai
 * @property string|null $ghi_chu
 * @property string|null $ly_do_dieu_chinh
 * @property int|null $dieu_chinh_boi
 * @property \Carbon\CarbonImmutable|null $dieu_chinh_luc
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Admin|null $nguoiDieuChinh
 * @property-read \App\Models\Admin $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereAnhRa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereAnhVao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereDiTrePhut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereDieuChinhBoi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereDieuChinhLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereGhiChu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereGioRa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereGioVao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereIdNhanvien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereLyDoDieuChinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereNgayChamCong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereTongCong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereTongGio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereTrangThai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChamCong whereUpdatedAt($value)
 */
	class ChamCong extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_cuoc_tro_chuyen
 * @property int $id_nguoigui
 * @property string|null $nguoigui_type
 * @property string|null $noidung
 * @property string|null $duongdan_dinhkem
 * @property string|null $ten_dinhkem
 * @property bool $daxem
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Conversation|null $conversation
 * @property-read mixed $duongdan_dinhkem_url
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $sender
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereDaxem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereDuongdanDinhkem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereIdCuocTroChuyen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereIdNguoigui($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereNguoiguiType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereNoidung($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereTenDinhkem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereUpdatedAt($value)
 */
	class ChatMessage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ten
 * @property string $mamau
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color whereMamau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color whereTen($value)
 */
	class Color extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_combo
 * @property string $ten_combo
 * @property string|null $hinhanh
 * @property string|null $mota
 * @property numeric $giakhuyenmai
 * @property int $trangthai
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SanPham> $sanPhams
 * @property-read int|null $san_phams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BienThe> $triggeringVariants
 * @property-read int|null $triggering_variants_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo whereGiakhuyenmai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo whereHinhanh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo whereIdCombo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo whereMota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo whereTenCombo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Combo whereUpdatedAt($value)
 */
	class Combo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_combo
 * @property int $id_sanpham
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComboSanPham newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComboSanPham newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComboSanPham query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComboSanPham whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComboSanPham whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComboSanPham whereIdCombo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComboSanPham whereIdSanpham($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ComboSanPham whereUpdatedAt($value)
 */
	class ComboSanPham extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_khachhang
 * @property string|null $tin_nhan_cuoi
 * @property \Carbon\CarbonImmutable|null $tin_nhan_cuoi_luc
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessage> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereTinNhanCuoi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereTinNhanCuoiLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUpdatedAt($value)
 */
	class Conversation extends \Eloquent {}
}

namespace App\Models{
/**
 * Lưu đánh giá sản phẩm, trạng thái kiểm duyệt và phản hồi của quản trị viên.
 *
 * @property int $id_danhgia
 * @property int $id_dathang
 * @property int $id_bienthe
 * @property int $user_id
 * @property int $danhgia
 * @property string|null $binhluan
 * @property string $trangthai
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property-read \App\Models\BienThe $bienThe
 * @property-read \App\Models\DatHang $datHang
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia whereBinhluan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia whereDanhgia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia whereIdBienthe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia whereIdDanhgia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia whereIdDathang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhGia whereUserId($value)
 */
	class DanhGia extends \Eloquent {}
}

namespace App\Models{
/**
 * Đại diện danh mục sản phẩm và quan hệ danh mục cha, con, thuộc tính.
 *
 * @property int $id_danhmuc
 * @property int|null $id_danhmuc_cha
 * @property string $ten_danhmuc
 * @property string $trangthai
 * @property-read \App\Models\DanhMucCha|null $danhMucCha
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMuc newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMuc newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMuc query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMuc whereIdDanhmuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMuc whereIdDanhmucCha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMuc whereTenDanhmuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMuc whereTrangthai($value)
 */
	class DanhMuc extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_danhmuc_cha
 * @property string $ten_danhmuc
 * @property string $trangthai
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DanhMuc> $children
 * @property-read int|null $children_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMucCha newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMucCha newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMucCha query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMucCha whereIdDanhmucCha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMucCha whereTenDanhmuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DanhMucCha whereTrangthai($value)
 */
	class DanhMucCha extends \Eloquent {}
}

namespace App\Models{
/**
 * Đại diện đơn hàng, trạng thái thanh toán, vận chuyển và thông tin nhận hàng.
 *
 * @property int $id_dathang
 * @property int $id_khachhang
 * @property int|null $id_nhanvien
 * @property numeric $tongtien
 * @property string $trangthai
 * @property string|null $diachi
 * @property string|null $PTTT
 * @property string $trang_thai_thanh_toan
 * @property string|null $nha_cung_cap_thanh_toan
 * @property string|null $ma_don_hang_thanh_toan
 * @property string|null $ma_yeu_cau_thanh_toan
 * @property string|null $ma_giao_dich_thanh_toan
 * @property int|null $ma_ket_qua_thanh_toan
 * @property string|null $thong_bao_thanh_toan
 * @property string|null $kieu_thanh_toan
 * @property \Carbon\CarbonImmutable|null $thanh_toan_luc
 * @property array<array-key, mixed>|null $du_lieu_thanh_toan
 * @property int|null $id_khuyenmai
 * @property numeric $giam_gia
 * @property int $xu_dung
 * @property int $xu_nhan
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $lydo
 * @property string|null $minh_chung_hoan_tien
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DatHangChiTiet> $chi_tiets
 * @property-read int|null $chi_tiets_count
 * @property mixed $user_id
 * @property-read \App\Models\Admin|null $nhanVien
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereDiachi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereDuLieuThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereGiamGia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereIdDathang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereIdKhuyenmai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereIdNhanvien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereKieuThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereLydo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereMaDonHangThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereMaGiaoDichThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereMaKetQuaThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereMaYeuCauThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereMinhChungHoanTien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereNhaCungCapThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang wherePTTT($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereThanhToanLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereThongBaoThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereTongtien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereTrangThaiThanhToan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereXuDung($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHang whereXuNhan($value)
 */
	class DatHang extends \Eloquent {}
}

namespace App\Models{
/**
 * Lưu sản phẩm, số lượng và giá tại thời điểm phát sinh một đơn hàng.
 *
 * @property int $id
 * @property int $id_dathang
 * @property int $id_bienthe
 * @property int $soluong
 * @property numeric $gia
 * @property int|null $id_combo
 * @property string|null $id_nhom_combo
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property bool $hoantien
 * @property-read \App\Models\BienThe $bienThe
 * @property-read \App\Models\Combo|null $combo
 * @property-read \App\Models\DatHang $datHang
 * @property-read bool $is_refund
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereGia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereHoantien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereIdBienthe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereIdCombo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereIdDathang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereIdNhomCombo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereSoluong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatHangChiTiet whereUpdatedAt($value)
 */
	class DatHangChiTiet extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_diachi
 * @property int $id_user
 * @property string $tinh_thanhpho
 * @property string $phuong_xa
 * @property string $diachi_cuthe
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string $loai_diachi
 * @property bool $mac_dinh
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read string $dia_chi_day_du
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereDiachiCuthe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereIdDiachi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereIdUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereLoaiDiachi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereMacDinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi wherePhuongXa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereTinhThanhpho($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiaChi withoutTrashed()
 */
	class DiaChi extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_khachhang
 * @property string $ngay_diem_danh
 * @property int $streak
 * @property int $so_xu_nhan
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh whereNgayDiemDanh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh whereSoXuNhan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh whereStreak($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiemDanh whereUpdatedAt($value)
 */
	class DiemDanh extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_nhanvien
 * @property string $loai_nghi
 * @property string $thoi_luong
 * @property \Carbon\CarbonImmutable $tu_ngay
 * @property \Carbon\CarbonImmutable $den_ngay
 * @property string $ly_do
 * @property string|null $minh_chung
 * @property string|null $nguoi_ban_giao
 * @property string|null $ghi_chu_ban_giao
 * @property string $trang_thai
 * @property string|null $phan_hoi_quan_ly
 * @property int|null $xu_ly_boi
 * @property \Carbon\CarbonImmutable|null $xu_ly_luc
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Admin|null $nguoiXuLy
 * @property-read \App\Models\Admin $nhanVien
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereDenNgay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereGhiChuBanGiao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereIdNhanvien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereLoaiNghi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereLyDo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereMinhChung($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereNguoiBanGiao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi wherePhanHoiQuanLy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereThoiLuong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereTrangThai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereTuNgay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereXuLyBoi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonXinNghi whereXuLyLuc($value)
 */
	class DonXinNghi extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_sanpham_flashsale
 * @property int $id_danhsach
 * @property int $id_bienthe
 * @property float $gia_flash_sale
 * @property int $so_luong_gioi_han
 * @property int $so_luong_da_ban
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\BienThe|null $bienThe
 * @property-read \App\Models\FlashSaleSession|null $session
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct whereGiaFlashSale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct whereIdBienthe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct whereIdDanhsach($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct whereIdSanphamFlashsale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct whereSoLuongDaBan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct whereSoLuongGioiHan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleProduct whereUpdatedAt($value)
 */
	class FlashSaleProduct extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_session
 * @property string $ten_dot
 * @property \Carbon\CarbonImmutable|null $thoi_gian_bat_dau
 * @property \Carbon\CarbonImmutable|null $thoi_gian_ket_thuc
 * @property int $trang_thai
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FlashSaleProduct> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession whereIdSession($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession whereTenDot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession whereThoiGianBatDau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession whereThoiGianKetThuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession whereTrangThai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FlashSaleSession whereUpdatedAt($value)
 */
	class FlashSaleSession extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_giatri
 * @property int $id_thuoctinh
 * @property string $giatri
 * @property numeric $gia_cong_them
 * @property array<array-key, mixed>|null $danh_muc_ids
 * @property int $trangthai
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BienThe> $bienThes
 * @property-read int|null $bien_thes_count
 * @property-read \App\Models\ThuocTinh $thuocTinh
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiaTriThuocTinh newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiaTriThuocTinh newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiaTriThuocTinh query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiaTriThuocTinh whereDanhMucIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiaTriThuocTinh whereGiaCongThem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiaTriThuocTinh whereGiatri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiaTriThuocTinh whereIdGiatri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiaTriThuocTinh whereIdThuoctinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiaTriThuocTinh whereTrangthai($value)
 */
	class GiaTriThuocTinh extends \Eloquent {}
}

namespace App\Models{
/**
 * Lưu một mặt hàng hoặc nhóm combo trong giỏ hàng của người dùng.
 *
 * @property int $id_giohang
 * @property int $id_khachhang
 * @property int $id_bienthe
 * @property int $soluong
 * @property int|null $id_combo
 * @property string|null $id_nhom_combo
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\BienThe $bienThe
 * @property-read \App\Models\Combo|null $combo
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang whereIdBienthe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang whereIdCombo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang whereIdGiohang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang whereIdNhomCombo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang whereSoluong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GioHang whereUpdatedAt($value)
 */
	class GioHang extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_nhanvien
 * @property string $loai_ca
 * @property \Carbon\CarbonImmutable $ngay_bat_dau
 * @property \Carbon\CarbonImmutable|null $ngay_ket_thuc
 * @property array<array-key, mixed> $thu_lam_viec
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Admin $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien whereIdNhanvien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien whereLoaiCa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien whereNgayBatDau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien whereNgayKetThuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien whereThuLamViec($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichLamNhanVien whereUpdatedAt($value)
 */
	class LichLamNhanVien extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_lichsu
 * @property int $id_khachhang
 * @property int|null $id_vongquay
 * @property string $ten_qua
 * @property string $loai_qua
 * @property string|null $gia_tri_qua
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\VongQuay|null $vongQuay
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay whereGiaTriQua($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay whereIdLichsu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay whereIdVongquay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay whereLoaiQua($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay whereTenQua($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LichSuQuay whereUpdatedAt($value)
 */
	class LichSuQuay extends \Eloquent {}
}

namespace App\Models{
/**
 * Lưu liên hệ, yêu cầu tư vấn hoặc lịch hẹn showroom của khách hàng.
 *
 * @property int $id
 * @property string|null $hoten
 * @property string|null $email
 * @property string|null $sodienthoai
 * @property string|null $noidung
 * @property string|null $trangthai
 * @property string|null $phanhoi
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property string|null $danhmuc
 * @property string|null $phan_hoi_luc
 * @property string|null $loai_yeu_cau
 * @property int|null $showroom_id
 * @property string|null $showroom_ten
 * @property string|null $showroom_diachi
 * @property string|null $ngay_hen
 * @property string|null $khung_gio
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereDanhmuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereHoten($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereKhungGio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereLoaiYeuCau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereNgayHen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereNoidung($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe wherePhanHoiLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe wherePhanhoi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereShowroomDiachi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereShowroomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereShowroomTen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereSodienthoai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LienHe whereUpdatedAt($value)
 */
	class LienHe extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $tieude
 * @property string $slug
 * @property string $danhmuc
 * @property string $tacgia
 * @property string|null $hinhanh
 * @property string|null $mota_hinhanh
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $seo_keywords
 * @property string|null $canonical_url
 * @property bool $no_index
 * @property bool $noi_bat
 * @property bool $ghim
 * @property string $trangthai
 * @property string $workflow_status
 * @property \Carbon\CarbonImmutable|null $dang_luc
 * @property \Carbon\CarbonImmutable|null $reviewed_at
 * @property string|null $reviewed_by
 * @property int $luotxem
 * @property int $reading_time
 * @property int $share_count
 * @property string|null $tomtat
 * @property string|null $noidung
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NewsRevision> $revisions
 * @property-read int|null $revisions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NewsTag> $tags
 * @property-read int|null $tags_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereCanonicalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereDangLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereDanhmuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereGhim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereHinhanh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereLuotxem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereMotaHinhanh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereNoIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereNoiBat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereNoidung($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereReadingTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereSeoDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereSeoKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereSeoTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereShareCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereTacgia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereTieude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereTomtat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereWorkflowStatus($value)
 */
	class News extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $news_id
 * @property int $version
 * @property string|null $editor
 * @property string|null $note
 * @property array<array-key, mixed> $snapshot
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision whereEditor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision whereNewsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision whereSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsRevision whereVersion($value)
 */
	class NewsRevision extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\News> $news
 * @property-read int|null $news_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsTag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsTag whereUpdatedAt($value)
 */
	class NewsTag extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $email
 * @property bool $active
 * @property string $token
 * @property \Carbon\CarbonImmutable|null $subscribed_at
 * @property \Carbon\CarbonImmutable|null $unsubscribed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber whereSubscribedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber whereUnsubscribedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscriber whereUpdatedAt($value)
 */
	class NewsletterSubscriber extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_nhom
 * @property string $ten_nhom
 * @property array<array-key, mixed>|null $danh_muc_ids
 * @property int $trangthai
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ThuocTinh> $thuocTinhs
 * @property-read int|null $thuoc_tinhs_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NhomThuocTinh newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NhomThuocTinh newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NhomThuocTinh query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NhomThuocTinh whereDanhMucIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NhomThuocTinh whereIdNhom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NhomThuocTinh whereTenNhom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NhomThuocTinh whereTrangthai($value)
 */
	class NhomThuocTinh extends \Eloquent {}
}

namespace App\Models{
/**
 * Đại diện chương trình khuyến mãi và các điều kiện sử dụng mã giảm giá.
 *
 * @property int $id
 * @property string|null $ten
 * @property string $danhmuc
 * @property string|null $code
 * @property string|null $ngay_su_kien
 * @property bool $tu_dong_gui
 * @property string|null $loai
 * @property int|null $giatri
 * @property string $mota
 * @property string|null $ngaybatdau
 * @property string|null $ngayketthuc
 * @property string|null $trangthai
 * @property int $congkhai
 * @property numeric|null $dieu_kien_tang
 * @property int|null $so_luong_phat
 * @property string|null $loai_dieu_kien
 * @property numeric|null $dieu_kien
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserVoucher> $userVouchers
 * @property-read int|null $user_vouchers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereCongkhai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereDanhmuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereDieuKien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereDieuKienTang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereGiatri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereLoai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereLoaiDieuKien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereMota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereNgaySuKien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereNgaybatdau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereNgayketthuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereSoLuongPhat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereTen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereTuDongGui($value)
 */
	class Promotion extends \Eloquent {}
}

namespace App\Models{
/**
 * Đại diện sản phẩm, thông tin bán hàng và các biến thể liên quan.
 *
 * @property int $id_sanpham
 * @property int $id_danhmuc
 * @property int $id_thuonghieu
 * @property array<array-key, mixed>|null $thong_so_ky_thuat Lưu trữ thông số kỹ thuật dưới dạng JSON
 * @property string $tenSP
 * @property string|null $SKU
 * @property string $trangthai
 * @property string|null $hinhanh
 * @property string|null $khoiluong
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BienThe> $bienThes
 * @property-read int|null $bien_thes_count
 * @property-read \App\Models\DanhMuc|null $danhMuc
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BienTheHinhAnh> $hinhAnhs
 * @property-read int|null $hinh_anhs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DanhGia> $reviews
 * @property-read int|null $reviews_count
 * @property-read \App\Models\ThuongHieu|null $thuongHieu
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham whereHinhanh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham whereIdDanhmuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham whereIdSanpham($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham whereIdThuonghieu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham whereKhoiluong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham whereSKU($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham whereTenSP($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham whereThongSoKyThuat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPham whereTrangthai($value)
 */
	class SanPham extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_khachhang
 * @property int $id_sanpham
 * @property string $xem_luc
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\SanPham|null $sanPham
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPhamDaXem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPhamDaXem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPhamDaXem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPhamDaXem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPhamDaXem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPhamDaXem whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPhamDaXem whereIdSanpham($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPhamDaXem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SanPhamDaXem whereXemLuc($value)
 */
	class SanPhamDaXem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_thuoctinh
 * @property string $ten_thuoctinh
 * @property int $id_nhom
 * @property int $trangthai
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GiaTriThuocTinh> $giatriThuocTinhs
 * @property-read int|null $giatri_thuoc_tinhs_count
 * @property-read \App\Models\NhomThuocTinh|null $nhomThuocTinh
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuocTinh newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuocTinh newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuocTinh query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuocTinh whereIdNhom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuocTinh whereIdThuoctinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuocTinh whereTenThuoctinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuocTinh whereTrangthai($value)
 */
	class ThuocTinh extends \Eloquent {}
}

namespace App\Models{
/**
 * Đại diện thương hiệu được gắn với các sản phẩm trong hệ thống.
 *
 * @property int $id_thuonghieu
 * @property string $ten_thuonghieu
 * @property array<array-key, mixed>|null $danh_muc_ids
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $logo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuongHieu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuongHieu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuongHieu query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuongHieu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuongHieu whereDanhMucIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuongHieu whereIdThuonghieu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuongHieu whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuongHieu whereTenThuonghieu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ThuongHieu whereUpdatedAt($value)
 */
	class ThuongHieu extends \Eloquent {}
}

namespace App\Models{
/**
 * Đại diện tài khoản khách hàng hoặc nhân viên và các quan hệ thuộc tài khoản.
 *
 * @property int $id
 * @property string $ten
 * @property string $email
 * @property string|null $anhdaidien
 * @property string|null $face_descriptor
 * @property int $face_registered
 * @property string|null $sodienthoai
 * @property string|null $so_cccd
 * @property string|null $ngay_cap_cccd
 * @property string|null $noi_cap_cccd
 * @property string|null $anh_cccd_mat_truoc
 * @property string|null $anh_cccd_mat_sau
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $matkhau
 * @property string $vaitro
 * @property string|null $id_facebook
 * @property string|null $id_google
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property \Carbon\CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string|null $api_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $ngaysinh
 * @property string|null $gioitinh
 * @property string $trangthai
 * @property int $xu
 * @property string|null $otp_khoiphuc
 * @property string|null $otp_khoiphuc_hethan_luc
 * @property \Carbon\CarbonImmutable|null $hoat_dong_cuoi_luc
 * @property int $luot_quay
 * @property-read \App\Models\AffiliateProfile|null $affiliateProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AffiliateReferral> $affiliateReferrals
 * @property-read int|null $affiliate_referrals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AffiliateWithdrawRequest> $affiliateWithdrawRequests
 * @property-read int|null $affiliate_withdraw_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DiaChi> $diaChis
 * @property-read int|null $dia_chis_count
 * @property string|null $avatar
 * @property-read mixed $cac_quyen
 * @property mixed $date_of_birth
 * @property string|null $gender
 * @property mixed $last_active_at
 * @property string|null $name
 * @property-read bool $online
 * @property string|null $password
 * @property string|null $phone
 * @property string|null $role
 * @property string|null $status
 * @property-read mixed $ten_vaitro_hienthi
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\AffiliateReferral|null $referredByAffiliate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserVoucher> $vouchers
 * @property-read int|null $vouchers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\XuHistory> $xuHistories
 * @property-read int|null $xu_histories_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAnhCccdMatSau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAnhCccdMatTruoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAnhdaidien($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFaceDescriptor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFaceRegistered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGioitinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHoatDongCuoiLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIdFacebook($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIdGoogle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLuotQuay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMatkhau($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNgayCapCccd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNgaysinh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNoiCapCccd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOtpKhoiphuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOtpKhoiphucHethanLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSoCccd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSodienthoai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTrangthai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVaitro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereXu($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $id_user
 * @property int|null $id_voucher
 * @property string|null $trang_thai
 * @property \Carbon\CarbonImmutable|null $ngay_nhan
 * @property \Carbon\CarbonImmutable|null $het_han_luc
 * @property \Carbon\CarbonImmutable|null $da_su_dung_luc
 * @property-read \App\Models\Promotion|null $promotion
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Promotion|null $voucher
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher whereDaSuDungLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher whereHetHanLuc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher whereIdUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher whereIdVoucher($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher whereNgayNhan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserVoucher whereTrangThai($value)
 */
	class UserVoucher extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id_vaitro
 * @property string $ten_vaitro
 * @property string $ma_vaitro
 * @property string|null $mo_ta
 * @property array<array-key, mixed>|null $quyen
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro whereIdVaitro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro whereMaVaitro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro whereMoTa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro whereQuyen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro whereTenVaitro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaiTro whereUpdatedAt($value)
 */
	class VaiTro extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ten
 * @property numeric $ti_le
 * @property string $loai
 * @property string|null $giatri
 * @property int|null $id_voucher
 * @property string|null $mau_sac
 * @property string|null $mau_chu
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Promotion|null $voucher
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereGiatri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereIdVoucher($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereLoai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereMauChu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereMauSac($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereTen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereTiLe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VongQuay whereUpdatedAt($value)
 */
	class VongQuay extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_khachhang
 * @property int $so_xu
 * @property string $loai_giao_dich
 * @property int|null $id_dathang
 * @property string|null $mo_ta
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\DatHang|null $order
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory whereIdDathang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory whereLoaiGiaoDich($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory whereMoTa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory whereSoXu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|XuHistory whereUpdatedAt($value)
 */
	class XuHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_khachhang
 * @property int $id_bienthe
 * @property int $soluong
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\BienThe|null $bienthe
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YeuThich newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YeuThich newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YeuThich query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YeuThich whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YeuThich whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YeuThich whereIdBienthe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YeuThich whereIdKhachhang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YeuThich whereSoluong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YeuThich whereUpdatedAt($value)
 */
	class YeuThich extends \Eloquent {}
}

