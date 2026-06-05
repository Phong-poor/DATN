import 'package:flutter/material.dart';

import '../../core/theme/app_theme.dart';

class NetworkImageBox extends StatelessWidget {
  const NetworkImageBox({
    required this.url,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.cacheWidth = 600,
    super.key,
  });

  final String url;
  final double? width;
  final double? height;
  final BoxFit fit;
  final int? cacheWidth;

  @override
  Widget build(BuildContext context) {
    if (url.isEmpty) return _placeholder();
    return Image.network(
      url,
      width: width,
      height: height,
      fit: fit,
      cacheWidth: cacheWidth,
      filterQuality: FilterQuality.low,
      errorBuilder: (_, _, _) => _placeholder(),
      loadingBuilder: (context, child, progress) => progress == null
          ? child
          : SizedBox(
              width: width,
              height: height,
              child: const Center(
                child: SizedBox.square(
                  dimension: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            ),
    );
  }

  Widget _placeholder() => Container(
    width: width,
    height: height,
    color: const Color(0xFFF1F5F9),
    alignment: Alignment.center,
    child: const Icon(
      Icons.laptop_windows_outlined,
      color: AppColors.muted,
      size: 30,
    ),
  );
}
