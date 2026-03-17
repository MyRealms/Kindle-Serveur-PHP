# Kindle Serveur PHP

![Kindle Serveur sketch](serveur.jpg)

> R.I.P — Kindle Serveur 2010 — 2025  
> Kelimeleri öne çıkaran, dünyayı sadeleştiren küçük bir pencereydi.  
> Yıllarca durdu. İzlendi. Bekledi.  
> Bir gün yerinden alınırken küçük bir şeye çarptı — ekran sustu.  
> Hikâye orada bitmedi. Bu kitap, o sessizlikte yazılmaya devam etti.

Kindle Serveur, eski bir Kindle 3 Keyboard'u boş boş bekletmek yerine onu bir e-ink dashboard'a dönüştürme fikrinden çıktı.

Bir dönem cihazın ekranı saat, hava durumu ve haber akışlarını gösteren sakin bir bilgi paneline dönüşsün istedim. Proje ilk başta küçük bir denemeydi, sonra uzadıkça farklı ekranlar, haber görünümleri ve tarayıcı benzeri sayfalar eklenmeye başladı.

Projeye neden tam olarak `Serveur` adını verdiğimi bugün ben de net hatırlamıyorum. İsim öyle kaldı, proje de o isimle devam etti.

Bir süre sonra Kindle elimden kayıp ekrana sert şekilde düştü ve cihaz R.I.P oldu. Cihaz gidince proje de sessizce kenara çekildi. Sonunda bunu sadece kapalı bir deneme olarak bırakmak yerine herkese açık hâle getirmeye karar verdim.

Bu repo, projenin PHP ile hazırlanan web sürümüdür.

## İçerik

- Ana giriş ve yönlendirme sayfası
- Saat ekranları
- Haber akışı ekranları
- Basit tarayıcı benzeri görünümler
- İsteğe bağlı parola koruması

## Gereksinimler

- PHP 8.0+

## Çalıştırma

```powershell
php -S localhost:8000
```

Ardından `http://localhost:8000/index.php` adresini açın.

## Yapılandırma

- Parola ayarı `auth_config.php` içindedir.
- Statik dosyalar aynı klasörde tutulur.
- Bu repo PHP sürümüne odaklanır; desktop sürümü ayrı repoda yer alır.

## Durum

Proje aktif bir cihaz üzerinde geliştirilmiyor, ama açık bir arşiv ve yeniden kullanılabilir bir temel olarak burada duruyor.
