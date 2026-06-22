// I Know java will fuck me up
// :D

import java.util.ArrayList;
import java.util.Scanner;

public class Main
{
    public static void main(String[] args)
    {
        ArrayList<Barang> daftarBarang = new ArrayList<>();
        Scanner input = new Scanner(System.in);

        while (true)
        {
            System.out.println("=== MiniMarket ===");
            System.out.println("1. Tambah Barang.");
            System.out.println("2. Lihat Semua Barang.");
            System.out.println("3. Cari Barang.");
            System.out.println("4. Hapus Barang.");
            System.out.println("5. Keluar.");
            System.out.print("Pilihan: ");

            int pilihan = input.nextInt();
            input.nextLine();

            switch (pilihan)
            {
                case 1:
                    Barang barangs = new Barang();

                    System.out.println("=== Tambah ===");
                    System.out.print("Nama      : ");
                    barangs.nama = input.nextLine();

                    System.out.print("Stock     : ");
                    barangs.stok = input.nextInt();

                    System.out.print("Harga     : ");
                    barangs.harga = input.nextInt();
                    input.nextLine();

                    daftarBarang.add(barangs);
                    System.out.println("Barang berhasil di tambahkan!\n");
                    break;

                case 2:
                    if (daftarBarang.size() == 0)
                    {
                        System.out.println("Belum ada barang");
                        break;
                    }
                    else
                    {
                        System.out.println("=== Daftar Barang ===");

                        for (int i = 0; i < daftarBarang.size(); i++)
                        {
                            System.out.println(
                                    (i + 1) + ". "
                                            + daftarBarang.get(i).nama
                                            + " | Stock: "
                                            + daftarBarang.get(i).stok
                                            + " | Harga: "
                                            + daftarBarang.get(i).harga
                            );
                        }

                        break;
                    }

                case 3:
                    if (daftarBarang.size() == 0)
                    {
                        System.out.println("=== Cari Barang ===");
                        System.out.println("Barang belum ada.");
                        break;
                    }

                    // else
                    // {
                    System.out.println("=== Cari Barang ===");

                    // Scanner namaCari = new Scanner(System.in);

                    System.out.print("Masukan nama barang: ");
                    String cari = input.nextLine();

                    //     boolean ditemukan = false;

                    //     for (int i = 0; i < daftarBarang.size(); i++)
                    //     {
                    //         // Scanner namaCari = new Scanner(System.in);

                    //         // System.out.print("Masukan nama barang: ");
                    //         // String cari = namaCari.nextLine();
                    //         // namaCari.nextLine();

                    //         if (daftarBarang.get(i).nama.equalsIgnoreCase(cari))
                    //         {
                    //             System.out.println("Ketemu barang yang kamu cari! " + daftarBarang.get(i).nama + ", Dengan stock: " + daftarBarang.get(i).stok + "X, dengan harga " + daftarBarang.get(i).harga);
                    //             ditemukan = true;
                    //             break;
                    //         }
                    //         else
                    //         {
                    //             if (!ditemukan)
                    //             {
                    //                 System.out.println("Oh maaf, Barang yang anda cari tidak ada atau belum tersedia");
                    //                 break;
                    //             }
                    //         }
                    //     }
                    // break;
                    // }

                    boolean ditemukan = false;

                    for (int i = 0; i < daftarBarang.size(); i++)
                    {
                        if (daftarBarang.get(i).nama.equalsIgnoreCase(cari))
                        {
                            System.out.println(
                                    "Ketemu barang yang kamu cari! "
                                            + daftarBarang.get(i).nama
                                            + ", Dengan stock: "
                                            + daftarBarang.get(i).stok
                                            + "X, dengan harga "
                                            + daftarBarang.get(i).harga
                            );

                            ditemukan = true;
                            break;
                        }
                    }

                    if (!ditemukan)
                    {
                        System.out.println("Oh maaf!, barang yang anda cari tidak ada atau belum tersedia");
                    }

                    break;

                case 4:
                    if (daftarBarang.size() == 0)
                    {
                        System.out.println("Belum ada barang untuk dihapus.");
                        break;
                    }

                    System.out.println("=== Pilih barang yang mau anda hapus ===");

                    for (int i = 0; i < daftarBarang.size(); i++)
                    {
                        System.out.println((i + 1) + ". " + daftarBarang.get(i).nama);
                    }

                    System.out.print("Masukkan nomor barang yang ingin dihapus: ");
                    int hapus = input.nextInt();
                    input.nextLine();

                    if (hapus >= 1 && hapus <= daftarBarang.size())
                    {
                        String namaBarang = daftarBarang.get(hapus - 1).nama;

                        daftarBarang.remove(hapus - 1);

                        System.out.println(namaBarang + " berhasil dihapus!");
                    }
                    else
                    {
                        System.out.println("Nomor barang tidak valid!");
                    }

                    break;

                case 5:
                    System.out.println("Terima kasih telah menggunakan MiniMarket.");
                    input.close();
                    return;

                default:
                    System.out.println("Pilihan tidak tersedia!");
                    break;
            }

            System.out.println();
        }
    }
}
